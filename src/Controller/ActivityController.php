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

            $sort = $request->query->get('sort', 'date');
            $order = $request->query->get('order', 'desc');

            // El usuario puede enviar "false", "0", o nada.
            $onlyFreeParam = $request->query->get('onlyfree');
            // Convertimos a boolean: false solo si enviaron "false" o "0", sino true por defecto
            $onlyFree = !($onlyFreeParam === 'false' || $onlyFreeParam === '0');

            // Contar total de registros antes de filtrar por paginación
            $countQb = $this->repo->createQueryBuilder('a_count');
            
            // Construir la consulta
            $qb = $this->repo->createQueryBuilder('a'); // 'a' es el alias de Activity


            // Filtro por tipo de actividad
            if ($type) {
                if (ActivityType::tryFrom($type) !== null) {
                    $qb->andWhere('a.type = :type')
                        ->setParameter('type', $type);
                    $countQb->andWhere('a_count.type = :type')
                        ->setParameter('type', $type);
                }
            }

            // Ordenar por - solo se permite 'date' según OpenAPI
            $sortField = 'date_start';  // Solo permitimos ordenar por fecha
            $orderSql = strtolower($order) === 'asc' ? 'ASC' : 'DESC';
            $qb->orderBy('a.' . $sortField, $orderSql);


            // Obtener total de items
            $totalItems = (int) $countQb->select('COUNT(a_count.id)')->getQuery()->getSingleScalarResult();

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
                    if ($dto->clients_signed >= $dto->max_participants) {
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
                    'total-items' => $totalItems
                ]
            ], 200);


            // Manejo de errores
        } catch (\Throwable $e) {
            return $this->json([
                'code' => 500,
                'description' => 'Error interno del servidor al recuperar actividades',
            ], 500);
        }
    }
}