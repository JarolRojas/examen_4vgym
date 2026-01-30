<?php

namespace App\DTO;

use App\Entity\Activity;

class ActivityDTO
{
    public function __construct(
        public int $id,
        public string $type,
        public int $max_participants,
        public int $clients_signed,
        public string $date_start,
        public string $date_end,
        public array $play_list
    ) {}

    public static function fromEntity(Activity $activity): self
    {
        $playlistDTOs = [];
        foreach ($activity->getPlaylists() as $p) {
            $playlistDTOs[] = PlaylistDTO::fromEntity($p);
        }

        $signedCount = $activity->getBookings()->count();

        return new self(
            $activity->getId(),
            $activity->getType()->value,
            $activity->getMaxParticipants(),
            $signedCount,
            $activity->getDateStart()->format('Y-m-d H:i:s'),
            $activity->getDateEnd()->format('Y-m-d H:i:s'),
            $playlistDTOs
        );
    }
}