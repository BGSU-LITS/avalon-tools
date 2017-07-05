<?php
/**
 * Cue Post Action Class
 * @author John Kloor <kloor@bgsu.edu>
 * @copyright 2017 Bowling Green State University Libraries
 * @license MIT
 */

namespace App\Action;

use App\Exception\RequestException;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * An class for the index action.
 */
class CuePostAction extends AbstractAction
{
    /**
     * Method called when class is invoked as an action.
     * @param Request $req The request for the action.
     * @param Response $res The response from the action.
     * @param array $args The arguments for the action.
     * @return Response The response from the action.
     */
    public function __invoke(Request $req, Response $res, array $args)
    {
        // Unused;
        $args;

        try {
            // Begin session for cue with the user's email address.
            $cue = ['email' => $req->getParsedBodyParam('email')];

            if (empty($cue['email'])) {
                throw new RequestException(
                    'An email address must be provided.'
                );
            }

            // Make sure files were uploaded.
            $files = $req->getUploadedFiles();

            if (empty($files['files'])) {
                throw new RequestException(
                    'A cue sheet must be uploaded.'
                );
            }

            // Create object to obtain metadata that can be reused in loop.
            $getID3 = new \getID3;

            // Loop through each uploaded file.
            foreach ($files['files'] as $file) {
                $name = $file->getClientFilename();

                // Make sure that there were no upload errors.
                if ($file->getError()) {
                    throw new RequestException('Upload failure: ' . $name);
                }

                // Check that the file appears to be a cue sheet.
                if (!preg_match('/\.cue$/', $name)) {
                    throw new RequestException('Invalid cue sheet: ' . $name);
                }

                // Move the uploaded file to a temporary location.
                $temp = tempnam(sys_get_temp_dir(), 'avalon') . '.cue';
                $file->moveTo($temp);

                // Get the metadata for the file.
                $info = $getID3->analyze($temp);

                if (empty($info['cue'])) {
                    throw new RequestException('Invalid cue sheet: ' . $name);
                }

                // Add the metadata to the session information.
                $cue['files'][$name] = $info['cue'];
            }

            // Store the session information.
            $this->session->avalon_cue = $cue;

            // Redirect to get the current URI.
            return $res->withRedirect($req->getUri());
        } catch (RequestException $exception) {
            // Add any exceptions from the request to the flash messages.
            $this->flash->addMessage(
                'danger',
                $exception->getMessage()
            );
        }

        // Redirect to the homepage on error.
        return $res->withRedirect($req->getUri()->getBasePath());
    }
}
