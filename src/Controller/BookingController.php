<?php

namespace App\Controller;

use App\Entity\Booking;
use App\DTO\BookingResponseDTO; // <--- IMPORTANTE: Usamos el DTO
use App\Enum\ClientType;
use App\Repository\ActivityRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bookings')]
class BookingController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ActivityRepository $activityRepo,
        private ClientRepository $clientRepo
    ) {}

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            // DECODIFICAR JSON
            $data = json_decode($request->getContent(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['code' => 400, 'description' => 'Formato JSON inválido'], 400);
            }

            $clientId = $data['client_id'] ?? null;
            $activityId = $data['activity_id'] ?? null;

            if (!$clientId || !$activityId) {
                return $this->json(['code' => 400, 'description' => 'Faltan datos: client_id y activity_id son obligatorios'], 400);
            }

            // BUSCAR ENTIDADES
            $client = $this->clientRepo->find($clientId);
            $activity = $this->activityRepo->find($activityId);

            if (!$client) return $this->json(['code' => 404, 'description' => 'El cliente no existe'], 404);
            if (!$activity) return $this->json(['code' => 404, 'description' => 'La actividad no existe'], 404);

            foreach ($client->getBookings() as $existingBooking) {
                // Comparamos IDs. Si ya tiene una reserva con el mismo ID de actividad...
                if ($existingBooking->getActivity()->getId() === $activity->getId()) {
                    return $this->json([
                        'code' => 400,
                        'description' => 'Este cliente ya tiene una reserva activa para esta misma actividad'
                    ], 400);
                }
            }

            // VALIDAR AFORO (PLAZAS)
            if ($activity->getBookings()->count() >= $activity->getMaxParticipants()) {
                return $this->json(['code' => 400, 'description' => 'La actividad está completa, no hay plazas'], 400);
            }

            // VALIDAR RESTRICCIÓN STANDARD (2 por semana)
            if ($client->getType() === ClientType::STANDARD) {
                $fechaActividad = $activity->getDateStart();
                
                // Clonar para sacar el Lunes y Domingo de esa semana
                $inicioSemana = (clone $fechaActividad)->modify('monday this week 00:00:00');
                $finSemana = (clone $fechaActividad)->modify('sunday this week 23:59:59');

                $reservasSemana = 0;
                foreach ($client->getBookings() as $reserva) {
                    $fecha = $reserva->getActivity()->getDateStart();
                    // Solo contamos si cae en esa semana
                    if ($fecha >= $inicioSemana && $fecha <= $finSemana) {
                        $reservasSemana++;
                    }
                }

                if ($reservasSemana >= 2) {
                    return $this->json([
                        'code' => 400,
                        'description' => 'Límite alcanzado: Los usuarios Standard solo pueden reservar 2 actividades por semana'
                    ], 400);
                }
            }

            // GUARDAR RESERVA
            $booking = new Booking();
            $booking->setClient($client);
            $booking->setActivity($activity);


            $this->em->persist($booking);
            $this->em->flush();

            $dto = BookingResponseDTO::fromEntity($booking, 'Reserva creada con éxito');
            return $this->json($dto, 200);

        } catch (\Exception $e) {
            return $this->json(['code' => 500, 'description' => 'Error interno'], 500);
        }
    }
}