<?php

declare(strict_types=1);

namespace Lits\Data;

use Lits\Exception\InvalidDataException;

final class CueTrackData
{
    public function __construct(
        public string $filename,
        public string $title,
        public string $begin,
        public ?string $end,
        public ?string $performer,
    ) {
    }

    /**
     * @param array<mixed> $track
     * @throws InvalidDataException
     */
    public static function fromArray(array $track): self
    {
        $filename = '';

        if (isset($track['datafile']['filename'])) {
            $filename = \trim((string) $track['datafile']['filename']);
        }

        if ($filename === '' || !isset($track['title'])) {
            throw new InvalidDataException();
        }

        return new static(
            $filename,
            (string) $track['title'],
            self::begin($track),
            null,
            isset($track['performer']) ? (string) $track['performer'] : null,
        );
    }

    public function modifyPrevious(?self $previous): void
    {
        if (
            $previous instanceof self &&
            $previous->filename === $this->filename
        ) {
            $previous->end = $this->begin;
        }
    }

    /**
     * @param array<mixed> $track
     * @throws InvalidDataException
     */
    private static function begin(array $track): string
    {
        if (
            !isset($track['index'][1]['minutes']) ||
            !isset($track['index'][1]['seconds']) ||
            !isset($track['index'][1]['frames'])
        ) {
            throw new InvalidDataException();
        }

        return \sprintf(
            '%02d:%02d.%02d',
            (int) $track['index'][1]['minutes'],
            (int) $track['index'][1]['seconds'],
            (int) $track['index'][1]['frames'] * 100 / 75,
        );
    }
}
