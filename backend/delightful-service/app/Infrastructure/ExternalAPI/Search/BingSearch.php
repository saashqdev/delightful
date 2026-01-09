<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Search;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Hyperf\Codec\Json;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function Hyperf\Config\config;

class BingSearch
{
    private const int DEFAULT_SEARCH_ENGINE_TIMEOUT = 30;

    private LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    /**
     * Execute Bing search with comprehensive parameters.
     *
     * @param string $query Search query
     * @param string $subscriptionKey Bing API subscription key
     * @param string $mkt Market code (e.g., zh-CN, en-US)
     * @param int $count Number of results (1-50)
     * @param int $offset Pagination offset (0-1000)
     * @param string $safeSearch Safe search level (Strict, Moderate, Off)
     * @param string $freshness Time filter (Day, Week, Month)
     * @param string $setLang UI language code
     * @return array Native Bing API response
     * @throws GuzzleException
     */
    public function search(
        string $query,
        string $subscriptionKey,
        string $mkt,
        int $count = 20,
        int $offset = 0,
        string $safeSearch = '',
        string $freshness = '',
        string $setLang = '',
        string $requestUrl = ''
    ): array {
        /*
         * use bing searchandreturnupdowntext.
         */
        if (empty($requestUrl)) {
            $requestUrl = trim(config('search.drivers.bing.endpoint'));
        }
        // ensure endpoint by /search 结tail
        if (! str_ends_with($requestUrl, '/search')) {
            $requestUrl = rtrim($requestUrl, '/') . '/search';
        }

        // buildfoundationqueryparameter
        $queryParams = [
            'q' => $query,
            'mkt' => $mkt,
            'count' => $count,
            'offset' => $offset,
        ];

        // addoptionalparameter
        if (! empty($safeSearch)) {
            $queryParams['safeSearch'] = $safeSearch;
        }

        if (! empty($freshness)) {
            $queryParams['freshness'] = $freshness;
        }

        if (! empty($setLang)) {
            $queryParams['setLang'] = $setLang;
        }

        // create Guzzle customerclientconfiguration
        $clientConfig = [
            'base_uri' => $requestUrl,
            'timeout' => self::DEFAULT_SEARCH_ENGINE_TIMEOUT,
            'headers' => [
                'Ocp-Apim-Subscription-Key' => $subscriptionKey,
                'Accept-Language' => $mkt,
            ],
        ];

        $attempt = 0;
        $maxAttempts = 2; // originalrequest + 1timeretry

        while ($attempt < $maxAttempts) {
            try {
                // ifisretry(thetwotimetry),disableSSLverify
                if ($attempt !== 0) {
                    $clientConfig['verify'] = false;
                    $this->logger->warning('Retrying request with SSL verification disabled', [
                        'endpoint' => $requestUrl,
                        'attempt' => $attempt + 1,
                    ]);
                }

                $client = new Client($clientConfig);

                // send GET request
                $response = $client->request('GET', '', [
                    'query' => $queryParams,
                ]);

                // getresponsebodycontent
                $body = $response->getBody()->getContents();

                // ifneedwill JSON convertforarrayorobject,canuse json_decode
                // requestsuccess,returndata
                return Json::decode($body);
            } catch (RequestException $e) {
                // ifhaveresponse,instructionisHTTPerror(4xx, 5xxetc),notretry
                if ($e->hasResponse()) {
                    $statusCode = $e->getResponse()?->getStatusCode();
                    $reason = $e->getResponse()?->getReasonPhrase();
                    $responseBody = $e->getResponse()?->getBody()->getContents();
                    $this->logger->error(sprintf('Bing search error HTTP %d %s: %s', $statusCode, $reason, $responseBody), [
                        'endpoint' => $requestUrl,
                        'statusCode' => $statusCode,
                    ]);
                    break; // HTTPerrornotretry,directlyjumpoutloop
                }
                $this->logger->warning('Network error occurred', [
                    'endpoint' => $requestUrl,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);

                ++$attempt;
            }
        }

        // ifwalktothiswithin,instruction havetryallfail
        throw new RuntimeException('Search engine error.');
    }
}
