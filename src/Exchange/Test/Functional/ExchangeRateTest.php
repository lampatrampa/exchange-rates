<?php

declare(strict_types=1);

namespace App\Exchange\Test\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class ExchangeRateTest extends WebTestCase
{

    public function testGetExchangeRate(): void
    {
        $client = static::createClient();

        $client->jsonRequest(
            Request::METHOD_GET,
            '/api/v1/exchange-rate?date=2010-06-04&currency=USD&baseCurrency=TRY'
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $this->assertNotEmpty($client->getResponse()->getContent());
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('rate', $responseData);
        $this->assertArrayHasKey('difference', $responseData);

        $this->assertIsFloat($responseData['rate']);
        $this->assertIsFloat($responseData['difference']);
    }
}
