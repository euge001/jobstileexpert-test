<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiTest extends WebTestCase
{
    public function testHome(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
    }

    public function testOrderAggregate(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/orders/aggregate?group_by=day');
        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testSearchEmpty(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/search?q=notfound');
        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
    }
}
