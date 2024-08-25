<?php

declare(strict_types=1);

namespace App\Exchange\Provider;

use App\Exchange\Exception\RateException;

abstract class AbstractRateProvider implements RateProviderInterface
{
    protected const PROVIDER_BASE_CURRENCY = '';

    /**
     * @param string $date
     * @return array{string:float}
     */
    abstract protected function fetchRatesForDate(string $date): array;

    public function getRate(string $date, string $currency, string $baseCurrency): float
    {
        $rates = $this->fetchRatesForDate($date);
        $currencyRate = $this->getRateFromRates($rates, $currency);
        $baseRate = $this->getRateFromRates($rates, $baseCurrency);
        return $currencyRate / $baseRate;
    }

    /**
     * @param array{string:float} $rates
     * @param string $currency
     * @return float
     */
    protected function getRateFromRates(array $rates, string $currency): float 
    {
        $currency = strtoupper($currency);
        
        if ($currency === static::PROVIDER_BASE_CURRENCY) {
            return 1.0;
        }
        
        if (isset($rates[$currency]) === false) {
            throw new RateException('Currency code ' . $currency . ' not found');
        }
        
        return $rates[$currency];
    }
}