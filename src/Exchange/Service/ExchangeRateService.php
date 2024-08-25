<?php

declare(strict_types=1);

namespace App\Exchange\Service;

use App\Exchange\Provider\RateProviderInterface;
use App\Exchange\Dto\Response\ExchangeRateDto;
use App\Exchange\Dto\Request\FetchExchangeRateDto;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ExchangeRateService implements ExchangeRateServiceInterface
{
    private const CACHE_TTL = 7 * 24 * 3600; // One week

    public function __construct(
        private readonly RateProviderInterface $rateProvider,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getExchangeRate(FetchExchangeRateDto $dto): ExchangeRateDto
    {
        if ($dto->getDate() === null || $dto->getCurrency() === null) {
            throw new \InvalidArgumentException('Wrong types of the exchange rate dto fields');
        }

        $cacheKey = $this->getCacheKey($dto->getDate(), $dto->getCurrency(), $dto->getBaseCurrency());

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($dto): ExchangeRateDto {
            $item->expiresAfter(self::CACHE_TTL);

            $rate = $this->rateProvider->getRate($dto->getDate(), $dto->getCurrency(), $dto->getBaseCurrency());

            $previousDate = strtotime($dto->getDate() . ' - 1 day');

            if ($previousDate === false) {
                throw new \RuntimeException('Failed previous rate date calculation');
            }

            $previousRate = $this->rateProvider->getRate(
                date('Y-m-d', $previousDate),
                $dto->getCurrency(),
                $dto->getBaseCurrency()
            );

            return new ExchangeRateDto(
                rate: round($rate, 6),
                difference: round($rate - $previousRate, 6),
            );
        });
    }

    private function getCacheKey(string $date, string $currency, string $baseCurrency): string
    {
        return 'exchange_rate_' . $date . '_' . $currency . '_' . $baseCurrency;
    }
}
