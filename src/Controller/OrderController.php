<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OrderController extends AbstractController
{
    #[Route('/api/orders/search', name: 'order_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->query->get('query', '');
        if ($query === '') {
            return $this->json(['error' => 'query parameter is required'], 400);
        }

        $client = HttpClient::create(['timeout' => 5]);
        try {
            $response = $client->request('POST', sprintf('http://%s:%s/search', getenv('MANTICORE_HOST') ?: 'manticore', getenv('MANTICORE_PORT') ?: '9308'), [
                'json' => [
                    'index' => 'orders',
                    'query' => [
                        'match' => [
                            '*' => $query,
                        ],
                    ],
                ],
            ]);

            return $this->json($response->toArray(false));
        } catch (\Exception $e) {
            return $this->json(['error' => 'Search service unavailable', 'message' => $e->getMessage()], 503);
        }
    }

    #[Route('/api/orders/{id}', name: 'order_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getOne(int $id, EntityManagerInterface $em): JsonResponse
    {
        $order = $em->getRepository(Order::class)->find($id);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], 404);
        }

        return $this->json([
            'id' => $order->getId(),
            'created_at' => $order->getCreatedAt()?->format('c'),
            'amount' => $order->getAmount(),
        ]);
    }

    #[Route('/api/orders/aggregate', name: 'order_aggregate', methods: ['GET'])]
    public function aggregate(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');
        $groupBy = $request->query->get('group_by', 'month');
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('per_page', 10);

        // Validate required parameters
        if (!$startDate || !$endDate) {
            return $this->json(['error' => 'start_date and end_date are required'], 400);
        }

        // Validate dates
        $constraints = new Assert\Collection([
            'start_date' => [new Assert\Date()],
            'end_date' => [new Assert\Date()],
        ]);

        $violations = $validator->validate([
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], $constraints);

        if (count($violations) > 0) {
            return $this->json(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
        }

        // Validate group_by
        $allowedGroupBy = ['day', 'week', 'month', 'year'];
        if (!in_array($groupBy, $allowedGroupBy)) {
            return $this->json(['error' => 'group_by must be one of: ' . implode(', ', $allowedGroupBy)], 400);
        }

        // Build date format for grouping
        $dateFormat = match ($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y'
        };

        // Build the query with pagination
        $qb = $em->createQueryBuilder();
        $qb->select(sprintf('DATE_FORMAT(o.created_at, \'%s\') as period, SUM(o.amount) as total_amount, COUNT(o.id) as order_count', $dateFormat))
           ->from(Order::class, 'o')
           ->where('o.created_at >= :start_date')
           ->andWhere('o.created_at <= :end_date')
           ->setParameter('start_date', $startDate . ' 00:00:00')
           ->setParameter('end_date', $endDate . ' 23:59:59')
           ->groupBy('period')
           ->orderBy('period', 'ASC');

        // Count total for pagination
        $countQb = clone $qb;
        $totalItems = count($countQb->getQuery()->getResult());

        // Apply pagination
        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);

        $result = $qb->getQuery()->getResult();

        // Calculate pagination info
        $totalPages = (int) ceil($totalItems / $perPage);

        return $this->json([
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'data' => $result,
        ]);
    }
}
