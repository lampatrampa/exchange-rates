<?php

declare(strict_types=1);

namespace App\Exchange\Test\Unit;

use App\Exchange\Exception\RateException;
use App\Exchange\Provider\CbrRateProvider;
use App\Exchange\Exception\CbrRateException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CbrRateProviderTest extends KernelTestCase
{
    private HttpClientInterface $httpClient;

    private CacheInterface $cache;

    private ResponseInterface $response;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->getMockBuilder(HttpClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this->getMockBuilder(CacheInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_OK);

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://www.cbr.ru/scripts/XML_daily.asp', ['query' => ['date_req' => '02/03/2021']])
            ->willReturn($this->response);
    }

    public function testGetRateSuccess(): void
    {
        if ($this->response instanceof MockObject) {
            $this->response->expects($this->once())
                ->method('getContent')
                ->willReturn($this->getXmlContent());
        }

        $provider = new CbrRateProvider($this->httpClient, $this->cache, $this->logger);

        $rate = $provider->getRate('2021-03-02', 'USD', 'RUB');

        $this->assertEquals(91.6012, $rate);
    }

    public function testGetCrossRateSuccess(): void
    {
        if ($this->response instanceof MockObject) {
            $this->response->expects($this->once())
                ->method('getContent')
                ->willReturn($this->getXmlContent());
        }

        $provider = new CbrRateProvider($this->httpClient, $this->cache, $this->logger);

        $rate = $provider->getRate('2021-03-02', 'AMD', 'USD');

        $this->assertEquals(round(0.236061/91.6012, 4), round($rate, 4));
    }

    public function testGetRateWithInvalidCurrencyException(): void
    {
        if ($this->response instanceof MockObject) {
            $this->response->expects($this->once())
                ->method('getContent')
                ->willReturn($this->getXmlContent());
        }

        $provider = new CbrRateProvider($this->httpClient, $this->cache, $this->logger);

        $this->expectException(RateException::class);
        $this->expectExceptionMessage('Currency code XXX not found');

        $provider->getRate('2021-03-02', 'XXX', 'RUB');
    }

    public function testGetRateWithWrongParameterException(): void
    {
        if ($this->response instanceof MockObject) {
            $this->response->expects($this->once())
                ->method('getContent')
                ->willReturn($this->getXmlErrorContent());
        }

        $provider = new CbrRateProvider($this->httpClient, $this->cache, $this->logger);

        $this->expectException(CbrRateException::class);
        $this->expectExceptionMessage('Error in CBR XML response: Error in parameters');

        $provider->getRate('2021-03-02', 'XXX', 'RUB');
    }

    private function getXmlContent(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ValCurs Date="02.03.2021" name="Foreign Currency Market">
    <script/>
    <Valute ID="R01235">
        <NumCode>840</NumCode>
        <CharCode>USD</CharCode>
        <Nominal>1</Nominal>
        <Name>Доллар США</Name>
        <Value>91,6012</Value>
        <VunitRate>91,6012</VunitRate>
    </Valute>
    <Valute ID="R01010">
        <NumCode>036</NumCode>
        <CharCode>AUD</CharCode>
        <Nominal>1</Nominal>
        <Name>Австралийский доллар</Name>
        <Value>61,5560</Value>
        <VunitRate>61,556</VunitRate>
    </Valute>
    <Valute ID="R01060">
        <NumCode>051</NumCode>
        <CharCode>AMD</CharCode>
        <Nominal>100</Nominal>
        <Name>Армянских драмов</Name>
        <Value>23,6061</Value>
        <VunitRate>0,236061</VunitRate>
    </Valute>
</ValCurs>
XML;
    }

    private function getXmlErrorContent(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ValCurs>
    <script/>
    Error in parameters
</ValCurs>
XML;
    }
}
