<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\ModelGateway\Facade\Open;

use Hyperf\HttpServer\Contract\RequestInterface;

abstract class AbstractOpenApi
{
    public function __construct(
        protected readonly RequestInterface $request,
    ) {
    }

    protected function getAccessToken(): string
    {
        // allsurfacecompatible openai  api_key format

        // 1. 按order尝试fromrequestheadmiddleget
        $headers = [
            'api-key',
            'llm-access-token',
        ];

        $token = $this->getTokenFromHeaders($headers);
        if (! empty($token)) {
            return $token;
        }

        // 2. from Authorization headmiddleget Bearer token
        $token = $this->getTokenFromBearerAuth();
        if (! empty($token)) {
            return $token;
        }

        // 3. from HTTP Basic Auth middleget token
        $token = $this->getTokenFromBasicAuth();
        if (! empty($token)) {
            return $token;
        }

        // 4. fromqueryparametermiddleget
        $apiKey = $this->request->query('api_key');
        if (! empty($apiKey)) {
            return $apiKey;
        }

        // 5. fromrequestbodymiddleget
        $parsedBody = $this->request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody['api_key'])) {
            return $parsedBody['api_key'];
        }

        return '';
    }

    /**
     * fromfinger定requestheadcolumntablemiddle按ordergettoken.
     */
    protected function getTokenFromHeaders(array $headerNames): string
    {
        foreach ($headerNames as $headerName) {
            if (! empty($this->request->getHeader($headerName))) {
                return $this->request->getHeader($headerName)[0];
            }
        }

        return '';
    }

    /**
     * from Authorization headmiddleget Bearer token.
     */
    protected function getTokenFromBearerAuth(): string
    {
        if (! empty($this->request->getHeader('authorization'))) {
            $authHeader = $this->request->getHeader('authorization')[0] ?? '';
            if (str_starts_with(strtolower($authHeader), 'bearer ')) {
                return substr($authHeader, 7);
            }
        }

        return '';
    }

    /**
     * from HTTP Basic Auth middleget token.
     */
    protected function getTokenFromBasicAuth(): string
    {
        if (! empty($this->request->getHeader('php-auth-user'))) {
            return $this->request->getHeader('php-auth-user')[0];
        }

        return '';
    }

    protected function getClientIps(): array
    {
        $serverParams = $this->request->getServerParams();

        $ips = [];
        $ipHeaders = ['x-forwarded-for', 'x-real-ip'];
        foreach ($ipHeaders as $header) {
            foreach ($this->request->getHeader($header) as $item) {
                $ips[] = trim($item);
            }
        }

        if (! empty($serverParams['remote_addr'])) {
            $ip = trim(explode(':', $serverParams['remote_addr'], 2)[0]);
            if (! empty($ip)) {
                $ips = array_merge($ips, [$ip]);
            }
        }

        return $ips;
    }
}
