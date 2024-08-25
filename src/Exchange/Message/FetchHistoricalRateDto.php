<?php

declare(strict_types=1);

namespace App\Exchange\Message;

use App\Core\Dto\DtoInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FetchHistoricalRateDto implements DtoInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        private string $date,

        #[Assert\NotBlank]
        #[Assert\Length(exactly: 3)]
        private string $currency,

        #[Assert\NotBlank]
        #[Assert\Length(exactly: 3)]
        private string $baseCurrency
    ) {
    }

    public function getDate(): string {
        return $this->date;
    }

    public function getCurrency(): string {
        return $this->currency;
    }

    public function getBaseCurrency(): string {
        return $this->baseCurrency;
    }
}
