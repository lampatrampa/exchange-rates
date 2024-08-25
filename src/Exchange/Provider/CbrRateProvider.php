<?php

declare(strict_types=1);

namespace App\Exchange\Provider;

use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Exchange\Exception\CbrRateException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CbrRateProvider extends AbstractRateProvider implements RateProviderInterface
{
    private const CBR_DAILY_URL = 'https://www.cbr.ru/scripts/XML_daily.asp';
    
    private const CACHE_TTL = 7 * 24 * 3600; // One week
    
    protected const PROVIDER_BASE_CURRENCY = 'RUB';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string,float>
     * @throws InvalidArgumentException
     */
    protected function fetchRatesForDate(string $date): array
    {
        $cacheKey = $this->getCacheKey($date);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($date) {
            $item->expiresAfter(self::CACHE_TTL);
            $xmlData = $this->fetchXmlData($date);
            return $this->parseXmlData($xmlData);
        });
    }

    private function getCacheKey(string $date): string
    {
        return 'cbr_rates_' . $date;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function fetchXmlData(string $date): string
    {
        $formattedDate = \DateTime::createFromFormat('Y-m-d', $date) ?: \DateTime::createFromFormat('d/m/Y', $date);
        if (!$formattedDate) {
            throw new CbrRateException('Invalid date format. Expected Y-m-d or d/m/Y.');
        }

        $response = $this->httpClient->request(Request::METHOD_GET, self::CBR_DAILY_URL, [
            'query' => ['date_req' => $formattedDate->format('d/m/Y')],
        ]);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new CbrRateException('Failed to fetch XML data from CBR');
        }

        return $response->getContent();
    }

    /**
     * @return array<string,float>
     * @throws Exception
     */
    private function parseXmlData(string $xmlData): array
    {
        $xml = new \SimpleXMLElement($xmlData);
        $rates = [];

        if (isset($xml->Valute) === false) {
            $errorMessage = (string)$xml !== ''
                ? 'Error in CBR XML response: ' . trim((string)$xml)
                : 'Root node Valute not found or not iterable';
            throw new CbrRateException($errorMessage);
        }

        foreach ($xml->Valute as $valute) {
            if (
                isset($valute->CharCode) === false
                || isset($valute->Nominal) === false
                || isset($valute->Value) === false
            ) {
                $id = isset($valute['ID']) ? (string)$valute['ID'] : 'Unknown';
                $this->logger->error('Wrong structure for the currency with ID: ' . $id);
                continue;
            }

            $currency = (string)$valute->CharCode;
            $rate = (float)str_replace(',', '.', (string)$valute->Value) / $valute->Nominal;
            $rates[strtoupper($currency)] = $rate;
        }

        return $rates;
    }
}