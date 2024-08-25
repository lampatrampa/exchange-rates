<?php

declare(strict_types=1);

namespace App\Exchange\Controller;

use App\Exchange\Dto\Request\FetchExchangeRateDto;
use App\Exchange\Dto\Response\ExchangeRateDto;
use App\Exchange\Service\ExchangeRateServiceInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag('Exchange Rate')]
#[Route('/exchange-rate',name: 'exchange_rate_')]
class ExchangeRateController extends AbstractController
{
    public function __construct(
        private readonly ExchangeRateServiceInterface $exchangeRateService,
    ) {
    }

    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Successful response',
        content: new OA\JsonContent(
            ref: new Model(type: ExchangeRateDto::class)
        )
    )]
    #[Route('', name: 'get', methods: [Request::METHOD_GET])]
    public function getExchangeRate(
        #[MapQueryString] FetchExchangeRateDto $dto = new FetchExchangeRateDto(),
    ): JsonResponse {

        $exchangeRate = $this->exchangeRateService->getExchangeRate($dto);

        return $this->json($exchangeRate);
    }
}
