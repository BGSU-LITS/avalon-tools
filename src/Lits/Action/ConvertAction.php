<?php

declare(strict_types=1);

namespace Lits\Action;

use Lits\Data\CueData;
use Psr\Http\Message\UploadedFileInterface as UploadedFile;
use Safe\Exceptions\FilesystemException;
use Slim\Exception\HttpInternalServerErrorException;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

use function Safe\tempnam;

final class ConvertAction extends AuthAction
{
    /** @throws HttpInternalServerErrorException */
    public function action(): void
    {
        $session = $this->session->get('avalon-tools');

        $context = [
            'email' => $session['email'] ?? null,
        ];

        if (isset($session['files']) && \is_array($session['files'])) {
            $context['files'] = $this->sessionFiles($session['files']);
        }

        try {
            $this->render($this->template(), $context);
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    /**
     * @param array<string, string> $data
     * @throws HttpInternalServerErrorException
     */
    public function post(
        ServerRequest $request,
        Response $response,
        array $data,
    ): Response {
        $this->setup($request, $response, $data);

        $post = $this->request->getParsedBody();

        $this->session->set('avalon-tools', [
            'email' => $post['email'] ?? null,
            'files' => $this->moveFiles($this->files()),
        ]);

        try {
            $this->redirect(
                $this->routeCollector->getRouteParser()->urlFor('convert'),
            );

            return $this->response;
        } catch (\Throwable $exception) {
            throw new HttpInternalServerErrorException(
                $this->request,
                null,
                $exception,
            );
        }
    }

    /**
     * @return list<UploadedFile>
     * @throws HttpInternalServerErrorException
     */
    private function files(): array
    {
        $files = $this->request->getUploadedFiles();

        if (!isset($files['files']) || !\is_array($files['files'])) {
            return [];
        }

        $result = [];

        foreach ($files['files'] as $file) {
            \assert($file instanceof UploadedFile);

            if ($file->getError() !== \UPLOAD_ERR_OK) {
                throw new HttpInternalServerErrorException(
                    $this->request,
                    'File upload failed with code ' .
                    (string) $file->getError(),
                );
            }

            $result[] = $file;
        }

        return $result;
    }

    /**
     * @param list<UploadedFile> $files
     * @return array<string, string>
     * @throws HttpInternalServerErrorException
     */
    private function moveFiles(array $files): array
    {
        $result = [];

        foreach ($files as $file) {
            try {
                $temp = tempnam(\sys_get_temp_dir(), 'avalon-tools');
                $file->moveTo($temp);
            } catch (FilesystemException $exception) {
                throw new HttpInternalServerErrorException(
                    $this->request,
                    'Could not move uploaded file',
                    $exception,
                );
            }

            $result[$temp] = (string) $file->getClientFilename();
        }

        return $result;
    }

    /**
     * @param array<mixed> $files
     * @return list<CueData>
     */
    private function sessionFiles(array $files): array
    {
        $result = [];

        foreach ($files as $key => $value) {
            if (!\is_string($key) || !\is_string($value)) {
                continue;
            }

            $result[] = CueData::fromFile($key, $value);
        }

        return $result;
    }
}
