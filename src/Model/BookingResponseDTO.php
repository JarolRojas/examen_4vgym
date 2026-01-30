<?php

namespace App\DTO;

use App\Entity\Booking;

class BookingResponseDTO
{
    public function __construct(
        public int $id,
        public string $activity,
        public string $client,
        public string $status
    ) {
    }

    public static function fromEntity(Booking $booking, string $message): self
    {
        return new self(
            $booking->getId(),
            $booking->getActivity()->getType()->value,
            $booking->getClient()->getName(),
            $message
        );
    }
}