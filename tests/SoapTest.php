<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SoapTest extends WebTestCase
{
    public function testCreateOrderViaSoap(): void
    {
        $client = static::createClient();
        $xml = '<?xml version="1.0"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <CreateOrder>
      <amount>99.99</amount>
      <created_at>2026-01-18T23:00:00</created_at>
    </CreateOrder>
  </soap:Body>
</soap:Envelope>';
        $client->request('POST', '/soap', [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
        ], $xml);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('<CreateOrderResponse>', $client->getResponse()->getContent());
    }
}
