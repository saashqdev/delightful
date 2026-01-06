<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\DelightfulAIApi\Kernel;

use Delightful\SdkBase\Kernel\Constant\RequestMethod;
use Delightful\SdkBase\SdkBase;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

abstract class AbstractApi
{
    protected SdkBase $sdkContainer;

    public function __construct(SdkBase $sdkContainer)
    {
        $this->sdkContainer = $sdkContainer;
    }

    protected function post(string $url, array $options): ResponseInterface
    {
        $parseUrl = parse_url($url);
        if (! isset($parseUrl['host'])) {
            $uri = $this->getHost() . $url;
        } else {
            $uri = $url;
        }
        $options[RequestOptions::HEADERS]['llm-access-token'] = $this->getAccessToken();

        return $this->sdkContainer->getClientRequest()->request(RequestMethod::Post, $uri, $options);
    }

    protected function getResponseData(ResponseInterface $response, bool $isExposeRealError = false): array
    {
        if ($response->getStatusCode() !== 200) {
            throw new DelightfulAIApiException('请求DelightfulApi失败，HTTP 状态码: ' . $response->getStatusCode());
        }
        $originContent = $response->getBody()->getContents();
        $response->getBody()->rewind();
        $responseBody = json_decode($originContent, true);
        $code = $responseBody['code'] ?? null;
        $message = $responseBody['message'] ?? null;
        $data = $responseBody['data'] ?? null;
        if ($code !== 1000) {
            throw new DelightfulAIApiException('请求DelightfulApi失败 ' . ($isExposeRealError ? ($message ?? $originContent) : ''));
        }

        return $data;
    }

    private function getHost(): string
    {
        $host = $this->sdkContainer->getConfig()->get('delightful_ai_api_host', '');
        if (empty($host)) {
            throw new RuntimeException('The delightful_ai_api_host host must be configured');
        }
        return $host;
    }

    private function getAccessToken()
    {
        $token = $this->sdkContainer->getConfig()->get('delightful_ai_api_access_token', '');
        if (empty($token)) {
            throw new RuntimeException('The delightful_ai_api_access_token must be configured');
        }
        return $token;
    }
}
