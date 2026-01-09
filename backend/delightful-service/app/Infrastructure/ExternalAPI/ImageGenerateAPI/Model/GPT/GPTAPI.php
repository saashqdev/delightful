<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\GPT;

use GuzzleHttp\Client;
use Hyperf\Codec\Json;

class GPTAPI
{
    // requesttimeouttime（秒）
    protected const REQUEST_TIMEOUT = 300;

    protected string $apiKey;

    protected string $baseUrl;

    public function __construct(string $apiKey, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ?? \Hyperf\Config\config('image_generate.gpt4o.host');
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

    /**
     * submitGPT4oimagegeneratetask
     */
    public function submitGPT4oTask(string $prompt, array $referImages = [], ?string $hookUrl = null): array
    {
        $client = new Client(['timeout' => self::REQUEST_TIMEOUT]);
        $response = $client->post($this->baseUrl . '/openai/4o-image/generations', [
            'headers' => [
                'TT-API-KEY' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => array_filter([
                'prompt' => $prompt,
                'referImages' => $referImages,
                'hookUrl' => $hookUrl,
            ]),
        ]);

        return Json::decode($response->getBody()->getContents());
    }

    /**
     * queryGPT4otaskresult.
     */
    public function getGPT4oTaskResult(string $jobId): array
    {
        $client = new Client(['timeout' => self::REQUEST_TIMEOUT]);
        $response = $client->post($this->baseUrl . '/openai/4o-image/fetch', [
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
}
