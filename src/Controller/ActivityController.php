<?php

namespace App\Controller;

use App\DTO\ActivityDTO;
use App\Repository\ActivityRepository;
use App\Enum\ActivityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/activities')]
class ActivityController extends AbstractController
{
    public function __construct(private ActivityRepository $repo)
    {
    }



    // GET /activities (query params: page, page_size, type, sort, order)
    #[Route(path: '', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        try {
            // Parámetros de paginación, filtro y ordenación
            $page = $request->query->getInt('page', 1);
            $pageSize = $request->query->getInt('page_size', 10);

            $type = $request->query->get('type');

            $sort = $request->query->get('sort', 'date_start');
            $order = $request->query->get('order', 'asc');

            // El usuario puede enviar "true", "1", o nada.
            $onlyFreeParam = $request->query->get('onlyfree');
            // Convertimos a boolean: true si enviaron "true" o "1"
            $onlyFree = ($onlyFreeParam === 'true' || $onlyFreeParam === '1');

            // Construir la consulta
            $qb = $this->repo->createQueryBuilder('a'); // 'a' es el alias de Activity


            // Filtro por tipo de actividad
            if ($type) {
                if (ActivityType::tryFrom($type) !== null) {
                    $qb->andWhere('a.type = :type')
                        ->setParameter('type', $type);
                }
            }

            // Ordenar por
            $sortField = ($sort === 'participants') ? 'max_participants' : 'date_start';
            $orderSql = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
            $qb->orderBy('a.' . $sortField, $orderSql);


            // Paginación
            $qb->setFirstResult(($page - 1) * $pageSize)
                ->setMaxResults($pageSize);

            $activities = $qb->getQuery()->getResult();

            // Mapear los resultados a JSON
            $data = [];
            foreach ($activities as $a) {
                // Delegamos la transformación al DTO
                $dto = ActivityDTO::fromEntity($a);

                // Filtro 'onlyfree' usando los datos del DTO
                if ($onlyFree) {
                    if ($dto->clients_signed >= $dto->participants_max) {
                        continue;
                    }
                }
                $data[] = $dto;
            }

            // Respuesta JSON con metadatos de paginación
            return $this->json([
                'data' => $data,
                'meta' => [
                    'page' => $page,
                    'limit' => $pageSize,
                    'count' => count($data)
                ]
            ], 200);


            // Manejo de errores
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Error interno del servidor al recuperar actividades',
            ], 500);
        }
    }
}