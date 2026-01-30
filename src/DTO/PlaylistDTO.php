<?php

namespace App\DTO;

use App\Entity\Playlist;

class PlaylistDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public int $duration_seconds
    ) {}

    public static function fromEntity(Playlist $playlist): self
    {
        return new self(
            $playlist->getId(),
            $playlist->getName(),
            $playlist->getDurationSeconds()
        );
    }
}