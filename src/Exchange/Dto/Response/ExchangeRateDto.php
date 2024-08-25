<?php

declare(strict_types=1);

namespace App\Exchange\Dto\Response;

use OpenApi\Attributes as OA;
use App\Core\Service\ApiDoc\OpenApiType;
use App\Core\Dto\DtoInterface;

final readonly class ExchangeRateDto implements DtoInterface
{
    public function __construct(
        #[OA\Property(
            type: OpenApiType::NUMBER,
            example: '100.00',
        )]
        private float $rate,
        #[OA\Property(
            type: OpenApiType::NUMBER,
            example: '0.52',
        )]
        private float $difference,
    ) {
    }

    public function getRate(): float
    {
        return $this->rate;
    }

    public function getDifference(): float
    {
        return $this->difference;
    }
}
