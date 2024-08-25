<?php

declare(strict_types=1);

namespace App\Exchange\MessageHandler;

use App\Exchange\Message\FetchHistoricalRateDto;
use App\Exchange\Service\ExchangeRateService;
use App\Exchange\Dto\Request\FetchExchangeRateDto;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
final readonly class FetchHistoricalRateHandler
{
    public function __construct(
        private ExchangeRateService $exchangeRateService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     * @throws UnrecoverableMessageHandlingException
     */
    public function __invoke(FetchHistoricalRateDto $message): void
    {
        try {
            $this->exchangeRateService->getExchangeRate(
                new FetchExchangeRateDto(
                    date: $message->getDate(),
                    currency: $message->getCurrency(),
                    baseCurrency: $message->getBaseCurrency(),
                )
            );
        } catch (Exception $e) {
            $this->logger->error(
                'Error on fetch rate (date: ' . $message->getDate()
                . ', currency: ' . $message->getCurrency()
                . ', baseCurrency: ' . $message->getBaseCurrency() . '): ' . $e->getMessage()
            );
            throw new UnrecoverableMessageHandlingException(
                'Failed to fetch historical rate: ' . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }
}