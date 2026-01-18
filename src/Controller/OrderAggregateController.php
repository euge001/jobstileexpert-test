<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class OrderAggregateController extends AbstractController
{
    #[Route('/api/orders/aggregate', name: 'orders_aggregate', methods: ['GET'])]
    public function __invoke(Request $request, Connection $connection): JsonResponse
    {
        $groupBy = (string) $request->query->get('group_by', 'month');
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min(100, (int) $request->query->get('per_page', 10)));

        $formatMap = [
            'day' => '%Y-%m-%d',
            'month' => '%Y-%m',
            'year' => '%Y',
        ];
        if (!isset($formatMap[$groupBy])) {
            return $this->json(['error' => 'group_by must be day|month|year'], 400);
        }

        $format = $formatMap[$groupBy];
        $offset = ($page - 1) * $perPage;

        $countSql = "SELECT COUNT(*) as total FROM (SELECT DATE_FORMAT(created_at, :fmt) as period FROM `order` GROUP BY period) t";
        $total = (int) $connection->fetchOne($countSql, ['fmt' => $format], [ParameterType::STRING]);
        $totalPages = (int) ceil($total / $perPage);

        $sql = "SELECT DATE_FORMAT(created_at, :fmt) as period, COUNT(*) as count FROM `order` GROUP BY period ORDER BY period DESC LIMIT :limit OFFSET :offset";
        $rows = $connection->fetchAllAssociative($sql, [
            'fmt' => $format,
            'limit' => $perPage,
            'offset' => $offset,
        ], [
            'fmt' => ParameterType::STRING,
            'limit' => ParameterType::INTEGER,
            'offset' => ParameterType::INTEGER,
        ]);

        return $this->json([
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'total_items' => $total,
            'data' => $rows,
        ]);
    }
}
