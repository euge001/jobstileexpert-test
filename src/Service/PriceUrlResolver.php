<?php

namespace App\Service;

final class PriceUrlResolver
{
    /**
     * @var array<string, string>
     */
    private array $map = [
        'cobsa|manual|manu7530bcbm-manualbaltic7-5x30' => 'https://tile.expert/en-us/tile/marca-corona/arteseta/a/k263-arteseta-camoscio-s000628660',
    ];

    public function resolve(string $factory, string $collection, string $article): string
    {
        $key = strtolower($factory).'|'.strtolower($collection).'|'.strtolower($article);
        if (isset($this->map[$key])) {
            return $this->map[$key];
        }

        $factorySlug = rawurlencode(strtolower($factory));
        $collectionSlug = rawurlencode(strtolower($collection));
        $articleSlug = rawurlencode(strtolower($article));

        return sprintf(
            'https://tile.expert/en-us/tile/%s/%s/a/%s',
            $factorySlug,
            $collectionSlug,
            $articleSlug
        );
    }
}
