<?php

namespace App\Controller;

use App\Repository\ClientRepository;
use App\DTO\ClientDTO; // <--- IMPORTANTE
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/clients')]
class ClientController extends AbstractController
{
    public function __construct(private ClientRepository $clientRepo) {}

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $client = $this->clientRepo->find($id);
            if (!$client) {
                return $this->json(['error' => 'Cliente no encontrado'], 404);
            }

            $wBookingsParam = $request->query->get('with_bookings');
            $withBookings = ($wBookingsParam === 'true' || $wBookingsParam === '1');

            $wStatsParam = $request->query->get('with_statistics');
            $withStats = ($wStatsParam === 'true' || $wStatsParam === '1');

            // Calcular Bookings (si se pide)
            $bookingsData = null;
            if ($withBookings) {
                $bookingsData = [];
                foreach ($client->getBookings() as $b) {
                    $bookingsData[] = [
                        'id' => $b->getId(),
                        'activity_id' => $b->getActivity()->getId(),
                        'activity_type' => $b->getActivity()->getType()->value,
                        'date' => $b->getActivity()->getDateStart()->format('Y-m-d H:i')
                    ];
                }
            }

            // Calcular Estadísticas (si se pide)
            $statsOutput = null;
            if ($withStats) {
                $tempStats = [];
                foreach ($client->getBookings() as $b) {
                    $act = $b->getActivity();
                    $year = $act->getDateStart()->format('Y');
                    $type = $act->getType()->value;
                    
                    $durationSecs = $act->getDateEnd()->getTimestamp() - $act->getDateStart()->getTimestamp();
                    $minutes = $durationSecs / 60;

                    if (!isset($tempStats[$year])) $tempStats[$year] = [];
                    if (!isset($tempStats[$year][$type])) $tempStats[$year][$type] = ['num_activities' => 0, 'num_minutes' => 0];

                    $tempStats[$year][$type]['num_activities']++;
                    $tempStats[$year][$type]['num_minutes'] += $minutes;
                }

                $statsOutput = [];
                foreach ($tempStats as $yearKey => $typesData) {
                    $statsByType = [];
                    foreach ($typesData as $typeKey => $values) {
                        $statsByType[] = ['type' => $typeKey, 'statistics' => $values];
                    }
                    $statsOutput[] = ['year' => (int)$yearKey, 'statistics_by_type' => $statsByType];
                }
            }

            $dto = ClientDTO::fromEntity($client, $bookingsData, $statsOutput);
            return $this->json($dto);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Error interno', 'details' => $e->getMessage()], 500);
        }
    }
}