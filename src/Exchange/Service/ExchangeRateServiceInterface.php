<?php

declare(strict_types=1);

namespace App\Exchange\Service;

use App\Exchange\Dto\Response\ExchangeRateDto;
use App\Exchange\Dto\Request\FetchExchangeRateDto;

interface ExchangeRateServiceInterface
{
    public function getExchangeRate(FetchExchangeRateDto $dto): ExchangeRateDto;
}