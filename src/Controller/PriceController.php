<?php

namespace App\Controller;

use App\Service\PriceFetcher;
use App\Service\PriceUrlResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PriceController extends AbstractController
{
    #[Route('/api/price', name: 'price_get', methods: ['GET'])]
    public function __invoke(Request $request, PriceUrlResolver $resolver, PriceFetcher $fetcher): JsonResponse
    {
        $factory = (string) $request->query->get('factory');
        $collection = (string) $request->query->get('collection');
        $article = (string) $request->query->get('article');

        if ($factory === '' || $collection === '' || $article === '') {
            return $this->json(['error' => 'factory, collection, article обязательны'], 400);
        }

        $url = $resolver->resolve($factory, $collection, $article);
        try {
            $price = $fetcher->fetchPrice($url);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'url' => $url,
            ], 502);
        }

        return $this->json([
            'price' => $price,
            'factory' => $factory,
            'collection' => $collection,
            'article' => $article,
        ]);
    }
}
