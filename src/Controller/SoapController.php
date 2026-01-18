<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SoapController extends AbstractController
{
    #[Route('/soap', name: 'soap_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        $xml = $request->getContent();
        if ($xml === '') {
            return new Response('Empty SOAP request', 400);
        }

        $doc = @simplexml_load_string($xml);
        if ($doc === false) {
            return new Response('Invalid SOAP XML', 400);
        }

        $namespaces = $doc->getNamespaces(true);
        $body = $doc->children($namespaces['soap'] ?? '')->Body ?? null;
        $payload = $body ? $body->children() : null;
        if (!$payload) {
            return new Response('Invalid SOAP body', 400);
        }

        $amount = (float) ($payload->CreateOrder->amount ?? 0);
        $createdAt = (string) ($payload->CreateOrder->created_at ?? '');

        $order = new Order();
        $order->setAmount($amount);
        $order->setCreatedAt($createdAt !== '' ? new \DateTime($createdAt) : new \DateTime());

        $em->persist($order);
        $em->flush();

        $responseXml = sprintf(
            '<?xml version="1.0"?>\n<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">\n  <soap:Body>\n    <CreateOrderResponse>\n      <id>%d</id>\n    </CreateOrderResponse>\n  </soap:Body>\n</soap:Envelope>',
            $order->getId()
        );

        return new Response($responseXml, 200, ['Content-Type' => 'text/xml']);
    }
}
