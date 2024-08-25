<?php

declare(strict_types=1);

namespace App\Exchange\Dto\Request;

use App\Core\Service\ApiDoc\OpenApiType;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;
use App\Core\Dto\DtoInterface;

final readonly class FetchExchangeRateDto implements DtoInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        #[OA\Property(
            type: OpenApiType::STRING,
            format: 'date',
            example: '2024-01-01',
            nullable: false,
        )]
        private ?string $date = null,

        #[Assert\NotBlank]
        #[Assert\Length(exactly: 3)]
        #[OA\Property(
            type: OpenApiType::STRING,
            example: 'CHF',
            nullable: false,
        )]
        private ?string $currency = null,

        #[Assert\Length(exactly: 3)]
        #[OA\Property(
            type: OpenApiType::STRING,
            default: 'RUB',
            example: 'NOK',
            nullable: true,
        )]
        private string $baseCurrency = 'RUB',
    ) {
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }
}
