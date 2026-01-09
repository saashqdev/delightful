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
use Hyperf\Contract\ConfigInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function Hyperf\Config\config;

class CloudswaySearch
{
    private const int DEFAULT_SEARCH_ENGINE_TIMEOUT = 30;

    private LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory, protected readonly ConfigInterface $config)
    {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    /**
     * Execute Cloudsway search.
     *
     * @param string $query searchquery词
     * @param string $requestUrl 完整 endpoint URL (from config)
     * @param string $apiKey api key for authorization (from config)
     * @param string $mkt Market code (not used by Cloudsway but kept for interface consistency)
     * @param int $count resultquantity (10/20/30/40/50)
     * @param int $offset paginationoffsetquantity
     * @param string $freshness timefilter (Day/Week/Month)
     * @param string $setLang languagecode (如 en-US)
     * @return array Cloudsway API response
     * @throws GuzzleException
     */
    public function search(
        string $query,
        string $requestUrl,
        string $apiKey,
        string $mkt,
        int $count = 20,
        int $offset = 0,
        string $freshness = '',
        string $setLang = ''
    ): array {
        // buildqueryparameter
        $queryParams = [
            'q' => $query,
            'count' => $count,
            'offset' => $offset,
        ];

        if (empty($requestUrl)) {
            $basePath = $this->config->get('search.cloudsway.base_path');
            $endpoint = $this->config->get('search.cloudsway.endpoint');
            // build完整 URL: https://{BasePath}/search/{Endpoint}/smart
            $requestUrl = rtrim($basePath, '/') . '/search/' . trim($endpoint, '/') . '/smart';
        }

        // addoptionalparameter
        if (! empty($freshness)) {
            $queryParams['freshness'] = $freshness;
        }

        if (! empty($setLang)) {
            $queryParams['setLang'] = $setLang;
        }

        // buildrequesthead
        $headers = [
            'Authorization' => 'Bearer ' . $apiKey,
            'Pragma' => 'no-cache',  // notusecache，保证实o clockproperty
        ];

        // create Guzzle customer端
        $client = new Client([
            'timeout' => self::DEFAULT_SEARCH_ENGINE_TIMEOUT,
            'headers' => $headers,
        ]);

        try {
            // send GET request
            $response = $client->request('GET', $requestUrl, [
                'query' => $queryParams,
            ]);

            // getresponsebody
            $body = $response->getBody()->getContents();
            $data = Json::decode($body);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $statusCode = $e->getResponse()?->getStatusCode();
                $reason = $e->getResponse()?->getReasonPhrase();
                $responseBody = $e->getResponse()?->getBody()->getContents();
                $this->logger->error(sprintf('Cloudsway Search HTTP %d %s: %s', $statusCode, $reason, $responseBody), [
                    'url' => $requestUrl,
                    'statusCode' => $statusCode,
                ]);
            } else {
                $this->logger->error('Cloudsway Search Error: ' . $e->getMessage(), [
                    'url' => $requestUrl,
                    'exception' => get_class($e),
                ]);
            }

            throw new RuntimeException('Cloudsway search engine error.');
        }

        return $data;
    }
}
