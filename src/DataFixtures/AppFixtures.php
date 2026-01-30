<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\Booking;
use App\Entity\Client;
use App\Entity\Playlist;
use App\Enum\ActivityType;
use App\Enum\ClientType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // CREAR CLIENTES
        $c1 = new Client();
        $c1->setName('Miguel Standard');
        $c1->setEmail('miguel@gym.com');
        $c1->setType(ClientType::STANDARD);
        $manager->persist($c1);

        $c2 = new Client();
        $c2->setName('Ana Premium');
        $c2->setEmail('ana@gym.com');
        $c2->setType(ClientType::PREMIUM);
        $manager->persist($c2);


        // CREAR ACTIVIDADES        
        $a1 = new Activity();
        $a1->setType(ActivityType::BODY_PUMP);
        $a1->setMaxParticipants(20);
        $a1->setDateStart(new \DateTime('+1 day 10:00')); 
        $a1->setDateEnd(new \DateTime('+1 day 11:00'));
        $manager->persist($a1);

        $a2 = new Activity();
        $a2->setType(ActivityType::SPINNING);
        $a2->setMaxParticipants(5);
        $a2->setDateStart(new \DateTime('+2 days 18:00'));
        $a2->setDateEnd(new \DateTime('+2 days 19:00'));
        $manager->persist($a2);

        // CREAR PLAYLISTS
        $p1 = new Playlist();
        $p1->setName('Eye of the Tiger');
        $p1->setDurationSeconds(240);
        $p1->setActivity($a1);
        $manager->persist($p1);

        $p2 = new Playlist();
        $p2->setName('Gonna Fly Now');
        $p2->setDurationSeconds(180);
        $p2->setActivity($a1);
        $manager->persist($p2);

        // CREAR UNA RESERVA DE PRUEBA
        $booking = new Booking();
        $booking->setClient($c1);
        $booking->setActivity($a1);
        $manager->persist($booking);

        // GUARDAR TODO EN BASE DE DATOS
        $manager->flush();
    }
}