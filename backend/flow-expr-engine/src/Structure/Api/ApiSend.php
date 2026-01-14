<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Api;

use BeDelightful\FlowExprEngine\ComponentContext;
use BeDelightful\FlowExprEngine\Kernel\Utils\Functions;
use BeDelightful\FlowExprEngine\Structure\Api\Safe\DefenseAgainstSSRF;
use BeDelightful\FlowExprEngine\Structure\Api\Safe\DefenseAgainstSSRFOptions;
use BeDelightful\FlowExprEngine\Structure\Api\StandardIO\Request as StandardRequest;
use BeDelightful\FlowExprEngine\Structure\Api\StandardIO\Response as StandardResponse;
use BeDelightful\FlowExprEngine\Structure\Expression\ExpressionDataSource\ExpressionDataSourceSystemFields;
use BeDelightful\SdkBase\Kernel\Constant\RequestMethod;
use GuzzleHttp\RequestOptions;
use Throwable;

class ApiSend
{
    private ?StandardRequest $request = null;

    private ?StandardResponse $response = null;

    private ?float $elapsedTime = null;

    private string $method;

    private string $uri;

    private array $options;

    /**
     * Timeout in seconds.
     * Default timeout is set to 5 seconds to prevent too many requests from occupying connection pool resources.
     * Recommendation: APIs taking more than 5s should be optimized internally.
     */
    private int $timeout;

    private ApiRequestOptions $apiRequestOptions;

    private ?DefenseAgainstSSRFOptions $defenseAgainstSSRFOptions;

    public function __construct(ApiRequestOptions $apiRequestOptions, int $timeout = 5, ?DefenseAgainstSSRFOptions $defenseAgainstSSRFOptions = null)
    {
        $this->defenseAgainstSSRFOptions = $defenseAgainstSSRFOptions;
        $this->timeout = $timeout;
        $this->apiRequestOptions = $apiRequestOptions;
        $this->method = $apiRequestOptions->getMethod();
        $this->uri = $apiRequestOptions->getUri();
        $this->options = [
            RequestOptions::QUERY => $apiRequestOptions->getParamsQuery(),
            RequestOptions::HEADERS => $apiRequestOptions->getHeaders(),
            RequestOptions::TIMEOUT => $this->timeout,
            RequestOptions::ALLOW_REDIRECTS => false,
        ];
        if ($apiRequestOptions->getProxy()) {
            $this->options[RequestOptions::PROXY] = $apiRequestOptions->getProxy();
        }
        $this->options[RequestOptions::VERIFY] = $apiRequestOptions->isVerify();
        switch ($apiRequestOptions->getApiRequestBodyType()) {
            case ApiRequestBodyType::Json:
                $this->options[RequestOptions::JSON] = $apiRequestOptions->getBody();
                break;
            case ApiRequestBodyType::XWwwFormUrlencoded:
            case ApiRequestBodyType::FormData:
                $this->options[RequestOptions::FORM_PARAMS] = $apiRequestOptions->getBody();
                break;
            default:
                break;
        }
    }

    public function getRequest(): ?StandardRequest
    {
        return $this->request;
    }

    public function getResponse(): ?StandardResponse
    {
        return $this->response;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function run(): self
    {
        try {
            $uri = $this->uri;
            if ($this->defenseAgainstSSRFOptions) {
                $defenseAgainstSSRF = new DefenseAgainstSSRF($uri, $this->defenseAgainstSSRFOptions);
                $uri = $defenseAgainstSSRF->getSafeUrl();
                $this->options[RequestOptions::HEADERS]['Host'] = $defenseAgainstSSRF->getHost();
            }

            // Parse query from URL into query parameters
            if (isset($this->options[RequestOptions::QUERY])) {
                $query = parse_url($uri, PHP_URL_QUERY);
                if ($query) {
                    parse_str($query, $queryParams);
                    $this->options[RequestOptions::QUERY] = array_merge($queryParams, $this->options[RequestOptions::QUERY]);
                }
            }

            $this->request = StandardRequest::make(
                $this->method,
                $uri,
                json_encode($this->apiRequestOptions->getBody()),
                $this->formatHeaders($this->options[RequestOptions::HEADERS])
            );
            $response = ComponentContext::getSdkContainer()->getClientRequest()->request(RequestMethod::from(strtoupper($this->method)), $this->uri, $this->options);
            $this->response = StandardResponse::makeSuccess(
                $response->getStatusCode(),
                $response->getBody()->getContents(),
                $this->formatHeaders($response->getHeaders())
            );
            $response->getBody()->rewind();
        } catch (Throwable $throwable) {
            $this->response = StandardResponse::makeFail($throwable->getCode(), $throwable->getMessage());
        }
        $this->elapsedTime = $this->getElapsedTime();
        $this->log();

        return $this;
    }

    public function show(): array
    {
        return [
            'elapsed_time' => $this->getElapsedTime(),
            'request' => $this->getRequest()?->show(),
            'response' => $this->getResponse()?->show(),
        ];
    }

    public function getElapsedTime(): ?float
    {
        if (is_null($this->elapsedTime) && $this->request && $this->response) {
            $this->elapsedTime = round(($this->response->getTime() - $this->request->getTime()) * 1000, 2);
        }
        return $this->elapsedTime;
    }

    public function getResponseSourceData(string $componentId): array
    {
        $config = [
            $componentId . '.' . ExpressionDataSourceSystemFields::GuzzleResponseHttpCode->value => $this->getResponse()->getCode(),
            $componentId . '.' . ExpressionDataSourceSystemFields::GuzzleResponseHeader->value => $this->getResponse()?->getHeader() ?? [],
            $componentId . '.' . ExpressionDataSourceSystemFields::GuzzleResponseBody->value => $this->getResponse()?->getArrayBody() ?? [],
        ];
        return Functions::unFlattenArray($config);
    }

    private function formatHeaders(array $originHeaders = []): array
    {
        $headers = [];
        foreach ($originHeaders as $headerKey => $headerValue) {
            $headerKey = strtolower($headerKey);
            if (is_array($headerValue)) {
                $headers[$headerKey] = implode(';', $headerValue);
            } else {
                $headers[$headerKey] = $headerValue;
            }
        }
        return $headers;
    }

    private function log(): void
    {
        Functions::logEnabled() && ComponentContext::getSdkContainer()->getLogger()->info('api_send', $this->show());
    }
}
