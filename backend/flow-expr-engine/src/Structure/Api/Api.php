<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Api;

use BeDelightful\FlowExprEngine\Builder\ExpressionBuilder;
use BeDelightful\FlowExprEngine\Exception\FlowExprEngineException;
use BeDelightful\FlowExprEngine\Kernel\Utils\Functions;
use BeDelightful\FlowExprEngine\Structure\Api\Safe\DefenseAgainstSSRFOptions;
use BeDelightful\FlowExprEngine\Structure\Expression\Expression;
use BeDelightful\FlowExprEngine\Structure\Expression\ExpressionType;
use BeDelightful\FlowExprEngine\Structure\Structure;
use BeDelightful\FlowExprEngine\Structure\StructureType;
use FastRoute\RouteParser\Std;

class Api extends Structure
{
    public StructureType $structureType = StructureType::Api;

    protected ApiMethod $method;

    /**
     * Domain e.g.: http://127.0.0.1:9501.
     */
    protected string $domain;

    /**
     * URI in expression structure, the actual URI is calculated from path.
     */
    protected ?Expression $uri = null;

    /**
     * For display only
     * Path e.g.: /api/v1/app/{appId}.
     */
    protected ?string $path = null;

    /**
     * For display only
     * URL assembled from domain+path, for display e.g. http://127.0.0.1:9501/api/v1/app/{appId}.
     */
    protected ?string $url = null;

    /**
     * Proxy.
     */
    protected string $proxy = '';

    /**
     * Authentication identifier.
     */
    protected string $auth = '';

    protected ApiRequest $request;

    public function __construct(ApiMethod $apiMethod, string $domain, string $path, string $proxy = '', string $auth = '')
    {
        $this->proxy = $proxy;
        $this->init($apiMethod, $domain, $path, $auth);
    }

    public function init(ApiMethod $apiMethod, string $domain, string $path, string $auth = ''): void
    {
        $this->method = $apiMethod;
        $this->setDomain($domain);
        $this->path = $path;
        $this->pathToUri($path);
        $this->getUrl();
        $this->setAuth($auth);
    }

    public function setProxy(string $proxy): void
    {
        $this->proxy = $proxy;
    }

    public function getAuth(): string
    {
        return $this->auth;
    }

    public function setAuth(string $auth): void
    {
        $this->auth = $auth;
    }

    public static function createByUrl(ApiMethod $apiMethod, string $url): Api
    {
        if (! Functions::isUrl($url)) {
            throw new FlowExprEngineException("[{$url}] not a valid URL");
        }

        // Pre-encode the URL to handle non-ASCII characters before parse_url
        $encodedUrl = self::preEncodeUrl($url);
        $parsedUrl = parse_url($encodedUrl);

        $scheme = $parsedUrl['scheme'] ?? 'http';
        $host = $parsedUrl['host'] ?? '';

        $domain = "{$scheme}://{$host}";
        if (isset($parsedUrl['port'])) {
            $domain .= ":{$parsedUrl['port']}";
        }
        $path = $parsedUrl['path'] ?? '';

        if (! empty($parsedUrl['query'])) {
            $path = "{$path}?{$parsedUrl['query']}";
        }

        return new Api($apiMethod, $domain, $path);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'method' => $this->getMethod()?->value,
            'domain' => $this->getDomain(),
            'path' => $this->getPath(),
            'uri' => $this->getUri()?->toArray(),
            'url' => $this->getUrl(),
            'proxy' => $this->proxy,
            'auth' => $this->auth,
            'request' => $this->getRequest()?->jsonSerialize(),
        ];
    }

    public function getAllFieldsExpressionItem(): array
    {
        $fields = [];
        if ($this->uri) {
            $fields = array_merge($fields, $this->uri->getAllFieldsExpressionItem());
        }
        return array_merge($fields, $this->request->getAllFieldsExpressionItem());
    }

    public function getApiRequestOptions(array $expressionFieldData = [], ?ApiRequestOptions $apiRequestOptions = null): ApiRequestOptions
    {
        if (! $apiRequestOptions) {
            $apiRequestOptions = new ApiRequestOptions();
        }
        $requestUri = $this->getRequestUri($expressionFieldData, $apiRequestOptions);
        $apiRequestOptions->setUri($requestUri);
        $apiRequestOptions->setMethod($this->getMethod()?->value);
        $apiRequestOptions->setApiRequestBodyType($this->getRequest()->getApiRequestBodyType());
        $apiRequestOptions->addBody($this->getRequest()->getSpecialBody()?->getKeyValue($expressionFieldData));
        $apiRequestOptions->addHeaders($this->getRequest()->getSpecialHeaders()?->getKeyValue($expressionFieldData));
        $apiRequestOptions->setProxy($this->proxy);

        return $apiRequestOptions;
    }

    public function send(bool $checkResponse = false, ?ApiRequestOptions $apiRequestOptions = null, int $timeout = 5, ?DefenseAgainstSSRFOptions $defenseAgainstSSRFOptions = null): ApiSend
    {
        if (! $apiRequestOptions) {
            $apiRequestOptions = $this->getApiRequestOptions();
        }
        $apiSend = new ApiSend($apiRequestOptions, $timeout, $defenseAgainstSSRFOptions);
        $apiSend->run();
        if ($checkResponse && $apiSend->getResponse()->isErr()) {
            throw new FlowExprEngineException($apiSend->getResponse()->getErrMessage());
        }
        return $apiSend;
    }

    public function getMethod(): ?ApiMethod
    {
        return $this->method;
    }

    public function getDomain(): string
    {
        return $this->domain ?? '';
    }

    public function setDomain(?string $domain): void
    {
        if (empty($domain)) {
            $this->domain = '';
            return;
        }
        // Check if domain is valid
        if (! Functions::isUrl($domain)) {
            throw new FlowExprEngineException("{$domain} is not a valid url");
        }
        $this->domain = $domain;
        $this->getUrl();
    }

    public function getRequest(): ?ApiRequest
    {
        return $this->request;
    }

    public function setRequest(?ApiRequest $request): void
    {
        $this->request = $request;
    }

    public function getUrl(): ?string
    {
        $this->url = $this->getDomain() . $this->getPath();
        return $this->url;
    }

    public function getUri(): ?Expression
    {
        return $this->uri;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Pre-encode URL to handle non-ASCII characters before parse_url.
     */
    private static function preEncodeUrl(string $url): string
    {
        // Find the query part manually to avoid parse_url corruption
        $queryPos = strpos($url, '?');
        if ($queryPos === false) {
            return $url;
        }

        $baseUrl = substr($url, 0, $queryPos);
        $queryString = substr($url, $queryPos + 1);

        // Encode the query string
        $encodedQuery = self::encodeQueryString($queryString);

        return $baseUrl . '?' . $encodedQuery;
    }

    /**
     * Encode non-ASCII characters in query parameters.
     */
    private static function encodeQueryString(string $query): string
    {
        // Split query into key-value pairs
        $queryParts = explode('&', $query);
        $encodedParts = [];

        foreach ($queryParts as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = explode('=', $part, 2);
                $encodedParts[] = $key . '=' . rawurlencode($value);
            } else {
                $encodedParts[] = rawurlencode($part);
            }
        }

        return implode('&', $encodedParts);
    }

    private function getRequestUri(array $expressionFieldData = [], ?ApiRequestOptions $apiRequestOptions = null): string
    {
        if (empty($this->getDomain())) {
            throw new FlowExprEngineException('domain cannot be empty');
        }

        if (! $apiRequestOptions) {
            $apiRequestOptions = new ApiRequestOptions();
        }

        $uri = '';
        if ($this->uri) {
            // Get paramsPath parameters, need to be injected into uri
            $paramsPath = $this->getRequest()->getSpecialParamsPath();
            if ($paramsPath) {
                $apiRequestOptions->addParamsPath($paramsPath->getKeyValue($expressionFieldData));
            }
            $uri = $this->getUri()->getResult($apiRequestOptions->getParamsPath());
        }

        $requestUri = $this->getDomain() . $uri;

        // Add query parameters
        $paramsQuery = $this->request->getSpecialParamsQuery();
        if ($paramsQuery) {
            $apiRequestOptions->addParamsQuery($paramsQuery->getKeyValue($expressionFieldData));
            $httpBuildQuery = http_build_query($apiRequestOptions->getParamsQuery());
            // Only add when the built query is not empty
            if (! empty($httpBuildQuery)) {
                // Check if original query parameters exist
                $parsedUrl = parse_url($requestUri);
                if (! empty($parsedUrl['query'])) {
                    $requestUri .= '&';
                } else {
                    $requestUri .= '?';
                }
                $requestUri .= $httpBuildQuery;
            }
        }

        return $requestUri;
    }

    private function pathToUri(string $path): void
    {
        if (empty($path)) {
            return;
        }
        $parser = new Std();
        $uriPart = $parser->parse($path)[0];
        if (! $uriPart) {
            return;
        }
        $uri = [];
        $count = count($uriPart);
        foreach ($uriPart as $index => $item) {
            if (is_string($item)) {
                if ($index === 0 && $count === 1) {
                    $value = "'{$item}'";
                } else {
                    if ($index === 0) {
                        $value = "'{$item}'.";
                    } elseif ($index === $count - 1) {
                        $value = ".'{$item}'";
                    } else {
                        $value = ".'{$item}'.";
                    }
                }

                $uri[] = [
                    'type' => ExpressionType::Input->value,
                    'value' => $value,
                    'name' => $item,
                    'args' => [],
                ];
            } else {
                $uri[] = [
                    'type' => ExpressionType::Field->value,
                    'value' => $item[0],
                    'name' => $item[0],
                    'args' => [],
                ];
            }
        }
        $this->uri = (new ExpressionBuilder())->build($uri);
    }
}
