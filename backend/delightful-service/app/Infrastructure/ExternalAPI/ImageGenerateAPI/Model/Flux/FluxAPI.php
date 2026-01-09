<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Flux;

use GuzzleHttp\Client;
use Hyperf\Codec\Json;

class FluxAPI
{
    // requesttimeouttime（second）
    protected const REQUEST_TIMEOUT = 30;

    protected string $apiKey;

    protected string $baseUrl;

    public function __construct(string $apiKey, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ?? \Hyperf\Config\config('image_generate.flux.host');
    }

    /**
     * setting API Key.
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * setting API 基础 URL.
     */
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * submitimagegeneratetask
     */
    public function submitTask(string $prompt, string $size, string $mode = 'flux1-dev'): array
    {
        $client = new Client(['timeout' => self::REQUEST_TIMEOUT]);
        $response = $client->post($this->baseUrl . '/flux/generate', [
            'headers' => [
                'TT-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'prompt' => $prompt,
                'size' => $size,
                'mode' => $mode,
            ],
        ]);

        return Json::decode($response->getBody()->getContents());
    }

    /**
     * querytaskresult.
     */
    public function getTaskResult(string $jobId): array
    {
        $client = new Client(['timeout' => self::REQUEST_TIMEOUT]);
        $response = $client->post($this->baseUrl . '/flux/fetch', [
            'headers' => [
                'TT-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'jobId' => $jobId,
            ],
        ]);

        return Json::decode($response->getBody()->getContents());
    }

    /**
     * getaccountinformation.
     */
    public function getAccountInfo(): array
    {
        $client = new Client();
        $response = $client->get($this->baseUrl . '/midjourney/v1/info', [
            'headers' => [
                'TT-API-KEY' => $this->apiKey,
                'Accept' => '*/*',
                'User-Agent' => 'Delightful-Service/1.0',
            ],
        ]);

        return Json::decode($response->getBody()->getContents());
    }
}
