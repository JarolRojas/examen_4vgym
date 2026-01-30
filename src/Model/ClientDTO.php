<?php

namespace App\DTO;

use App\Entity\Client;

class ClientDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $type,
        public ?array $activities_booked = null, 
        public ?array $activity_statistics = null 
    ) {}

    public static function fromEntity(Client $client, ?array $bookings = null, ?array $stats = null): self
    {
        return new self(
            $client->getId(),
            $client->getName(),
            $client->getEmail(),
            $client->getType()->value,
            $bookings,
            $stats  
        );
    }
}