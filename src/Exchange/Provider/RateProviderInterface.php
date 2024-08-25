<?php

declare(strict_types=1);

namespace App\Exchange\Provider;

use DateTimeImmutable;

interface RateProviderInterface
{
    public function getRate(string $date, string $currency, string $baseCurrency): float;
}
