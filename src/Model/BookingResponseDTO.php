<?php

namespace App\DTO;

use App\Entity\Booking;

class BookingResponseDTO
{
    public function __construct(
        public int $id,
        public ActivityDTO $activity,
        public int $client_id
    ) {
    }

    public static function fromEntity(Booking $booking, string $message = ''): self
    {
        return new self(
            $booking->getId(),
            ActivityDTO::fromEntity($booking->getActivity()),
            $booking->getClient()->getId()
        );
    }
}