<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpClient\HttpClient;

final class SearchController extends AbstractController
{
    #[Route('/api/search', name: 'search', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $q = (string) $request->query->get('q', '');
        if ($q === '') {
            return $this->json(['error' => 'q is required'], 400);
        }

        $client = HttpClient::create(['timeout' => 5]);
        $response = $client->request('GET', sprintf('http://%s:%s/search', getenv('MANTICORE_HOST') ?: 'manticore', getenv('MANTICORE_PORT') ?: '9308'), [
            'query' => [
                'index' => 'orders',
                'query' => $q,
            ],
        ]);

        return $this->json($response->toArray(false));
    }
}
