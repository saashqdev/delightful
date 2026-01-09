<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\VolcengineArk;

use Exception;
use GuzzleHttp\Client;
use Hyperf\Codec\Json;

class VolcengineArkAPI
{
    protected const REQUEST_TIMEOUT = 300;

    protected const API_ENDPOINT = 'https://ark.cn-beijing.volces.com/api/v3/images/generations';

    protected string $apiKey;

    protected string $baseUrl;

    public function __construct(string $apiKey, string $baseUrl = self::API_ENDPOINT)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * generategraphlike - 完all透传payloadgiveAPI.
     */
    public function generateImage(array $payload): array
    {
        return $this->makeRequest($payload);
    }

    /**
     * send HTTP request.
     */
    protected function makeRequest(array $payload): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        $client = new Client(['timeout' => self::REQUEST_TIMEOUT]);

        $response = $client->post($this->baseUrl, [
            'headers' => $headers,
            'json' => $payload,
        ]);

        $result = Json::decode($response->getBody()->getContents());

        if ($response->getStatusCode() !== 200) {
            $errorMessage = $result['error']['message'] ?? "HTTP error: {$response->getStatusCode()}";
            throw new Exception("VolcengineArk API requestfail: {$errorMessage}");
        }

        if (isset($result['error'])) {
            $errorMessage = $result['error']['message'] ?? 'Unknown error';
            $errorCode = $result['error']['code'] ?? 'unknown_error';
            throw new Exception("VolcengineArk API error [{$errorCode}]: {$errorMessage}");
        }

        return $result;
    }
}
