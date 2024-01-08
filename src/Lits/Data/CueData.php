<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Exception\InvalidDataException;

final class CueData
{
    /**
     * @param list<CueTrackData> $tracks
     * @param list<string> $date
     * @param list<string> $genre
     */
    public function __construct(
        public string $title,
        public string $performer,
        public array $date,
        public array $genre,
        public array $tracks,
    ) {
    }

    /**
     * @param array<mixed> $cue
     * @throws InvalidDataException
     */
    public static function fromArray(array $cue): self
    {
        if (!isset($cue['title'])) {
            throw new InvalidDataException('Cue sheet requires title');
        }

        if (!isset($cue['performer'])) {
            throw new InvalidDataException('Cue sheet requires performer');
        }

        if (!isset($cue['tracks']) || !\is_array($cue['tracks'])) {
            throw new InvalidDataException('Cue sheet requires tracks');
        }

        return new static(
            (string) $cue['title'],
            (string) $cue['performer'],
            self::comments('date', $cue),
            self::comments('genre', $cue),
            self::tracks($cue['tracks']),
        );
    }

    /** @throws InvalidDataException */
    public static function fromFile(
        string $filename,
        ?string $original = null,
    ): self {
        try {
            /** @var \getID3 $id3 */
            static $id3 = new \getID3();
        } catch (\getid3_exception $exception) { /** @phpstan-ignore-line */
            throw new InvalidDataException(
                'Cue sheet could not be read',
                0,
                $exception,
            );
        }

        $info = $id3->analyze($filename, null, $original ?? '');

        if (isset($info['error']) && \is_array($info['error'])) {
            /** @psalm-var array<string> */
            $errors = $info['error'];

            throw new InvalidDataException(
                'Invalid cue sheet ' . ($original ?? $filename) . ' - ' .
                \implode(', ', $errors),
            );
        }

        if (!isset($info['cue']) || !\is_array($info['cue'])) {
            throw new InvalidDataException(
                'Invalid cue sheet ' . ($original ?? $filename),
            );
        }

        return self::fromArray($info['cue']);
    }

    /**
     * @param array<mixed> $tracks
     * @return list<CueTrackData>
     */
    private static function tracks(array $tracks): array
    {
        $result = [];
        $previous = null;

        /** @psalm-var array<mixed> $track */
        foreach ($tracks as $track) {
            try {
                $current = CueTrackData::fromArray($track);
                $current->modifyPrevious($previous);
            } catch (InvalidDataException) {
                continue;
            }

            $result[] = $current;
            $previous = $current;
        }

        return $result;
    }

    /**
     * @param array<mixed> $cue
     * @return list<string>
     */
    private static function comments(string $type, array $cue): array
    {
        if (
            !isset($cue['comments']) ||
            !\is_array($cue['comments']) ||
            !isset($cue['comments'][$type])
        ) {
            return [];
        }

        if (!\is_array($cue['comments'][$type])) {
            return [(string) $cue['comments'][$type]];
        }

        $result = [];

        /** @psalm-var mixed $comment */
        foreach ($cue['comments'][$type] as $comment) {
            $result[] = (string) $comment;
        }

        return $result;
    }
}
