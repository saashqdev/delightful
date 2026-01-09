<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Midjourney;

use GuzzleHttp\Client;
use Hyperf\Codec\Json;

class MidjourneyAPI
{
    // requesttimeout时间（秒）
    protected const REQUEST_TIMEOUT = 30;

    protected string $apiKey;

    protected string $baseUrl;

    public function __construct(string $apiKey, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ?? \Hyperf\Config\config('image_generate.midjourney.host');
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
    public function submitTask(string $prompt, string $mode = 'fast'): array
    {
        $client = new Client(['timeout' => self::REQUEST_TIMEOUT]);
        $response = $client->post($this->baseUrl . '/midjourney/v1/imagine', [
            'headers' => [
                'TT-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'prompt' => $prompt,
                'mode' => $mode,
                'timeout' => 600,
                'getUImages' => true,
            ],
        ]);

        return Json::decode($response->getBody()->getContents());
    }

    /**
     * check Prompt 是否legal.
     */
    public function checkPrompt(string $prompt): array
    {
        $client = new Client(['timeout' => self::REQUEST_TIMEOUT]);
        $response = $client->post($this->baseUrl . '/midjourney/v1/promptCheck', [
            'headers' => [
                'TT-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'prompt' => $prompt,
            ],
        ]);

        return Json::decode($response->getBody()->getContents());
    }

    /**
     * querytask结果.
     */
    public function getTaskResult(string $jobId): array
    {
        $client = new Client(['timeout' => self::REQUEST_TIMEOUT]);
        $response = $client->post($this->baseUrl . '/midjourney/v1/fetch', [
            'headers' => [
                'TT-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'jobId' => $jobId,
            ],
        ]);

        return Json::decode((string) $response->getBody());
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
