<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

final class PriceFetcher
{
    public function fetchPrice(string $url): float
    {
        $client = HttpClient::create([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; TilePriceBot/1.0)',
                'Accept' => 'text/html',
            ],
            'timeout' => 10,
        ]);

        $response = $client->request('GET', $url);
        $status = $response->getStatusCode();
        if ($status >= 400) {
            throw new \RuntimeException('Price page not available');
        }

        $html = $response->getContent();
        $crawler = new Crawler($html);

        $text = '';
        $priceCandidates = [
            '[itemprop="price"]',
            '.price',
            '.product-price',
            '.product__price',
            '[data-price]',
        ];

        foreach ($priceCandidates as $selector) {
            if ($crawler->filter($selector)->count() > 0) {
                $text = $crawler->filter($selector)->first()->text();
                break;
            }
        }

        if ($text === '') {
            $text = $crawler->text();
        }

        if (!preg_match('/([0-9]+(?:[\.,][0-9]{1,2})?)/', $text, $matches)) {
            throw new \RuntimeException('Price not found');
        }

        $value = str_replace(',', '.', $matches[1]);
        return (float) $value;
    }
}
