<?php

declare(strict_types=1);

namespace App\Exchange\Test\Unit;

use App\Exchange\Dto\Response\ExchangeRateDto;
use App\Exchange\Service\ExchangeRateService;
use App\Exchange\Provider\RateProviderInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Cache\CacheItem;
use App\Exchange\Dto\Request\FetchExchangeRateDto;

class ExchangeRateServiceTest extends KernelTestCase
{
    /**
     * @throws InvalidArgumentException
     */
    public function testGetExchangeRate(): void
    {
        $rateProvider = $this->createMock(RateProviderInterface::class);
        $cache = $this->createMock(CacheInterface::class);

        $rateProvider->expects($this->exactly(2))
            ->method('getRate')
            ->willReturnOnConsecutiveCalls(73.8757, 73.8757);

        $cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($key, callable $callback) {
                return $callback(new CacheItem());
            });

        $service = new ExchangeRateService($rateProvider, $cache);

        $exchangeRateDto = new FetchExchangeRateDto(
            date: '2021-01-01',
            currency: 'USD'
        );

        $result = $service->getExchangeRate($exchangeRateDto);

        $this->assertInstanceOf(ExchangeRateDto::class, $result);
        $this->assertEquals(73.8757, $result->getRate());
        $this->assertEquals(0, $result->getDifference());
    }
}