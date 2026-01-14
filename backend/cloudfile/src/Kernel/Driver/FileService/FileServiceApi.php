<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel\Driver\FileService;

use BeDelightful\CloudFile\Kernel\Struct\CredentialPolicy;
use BeDelightful\SdkBase\Kernel\Constant\RequestMethod;
use BeDelightful\SdkBase\SdkBase;
use GuzzleHttp\RequestOptions;

class FileServiceApi
{
    private string $host;

    private string $platform;

    private string $key;

    private SdkBase $sdkContainer;

    public function __construct(SdkBase $sdkContainer, array $config)
    {
        $this->sdkContainer = $sdkContainer;
        $this->host = $config['host'];
        $this->platform = $config['platform'];
        $this->key = $config['key'];
    }

    public function getTemporaryCredential(CredentialPolicy $credentialPolicy, array $options = []): array
    {
        $route = FileServiceRouteManager::get('temporary-credential', $options);

        $policy = [];
        if ($credentialPolicy->getSizeMax() > 0) {
            $policy['size_max'] = $credentialPolicy->getSizeMax();
        }
        if ($credentialPolicy->getExpires() > 0) {
            $policy['expires'] = $credentialPolicy->getExpires();
        }
        if (! empty($credentialPolicy->getContentType())) {
            $policy['content_type'] = $credentialPolicy->getContentType();
        }
        if (! empty($credentialPolicy->getMimeType())) {
            $policy['mime_type'] = $credentialPolicy->getMimeType();
        }
        if (! empty($credentialPolicy->getDir())) {
            $policy['dir'] = $credentialPolicy->getDir();
        }
        if (! empty($credentialPolicy->getStsType())) {
            $policy['sts_type'] = $credentialPolicy->getStsType();
        }
        $policy['sts'] = $credentialPolicy->isSts();

        return $this->post($route, [
            'platform' => $this->platform,
            'key' => $this->key,
            'policy' => $policy,
        ], $options);
    }

    public function getPreSignedUrls(array $names, int $expires = 3600, array $options = []): array
    {
        $route = FileServiceRouteManager::get('pre-signed-urls', $options);

        return $this->post($route, [
            'platform' => $this->platform,
            'key' => $this->key,
            'file_names' => $names,
            'expires' => $expires,
        ], $options);
    }

    public function show(array $paths, array $options = []): array
    {
        $route = FileServiceRouteManager::get('show', $options);

        return $this->post($route, [
            'platform' => $this->platform,
            'key' => $this->key,
            'file_paths' => $paths,
            // Lenient mode. Check if file exists, do not throw exception, skip.
            'file_check_mode' => 2,
        ], $options);
    }

    public function getUrls(array $paths, array $downloadNames = [], int $expires = 3600, array $options = []): array
    {
        $route = FileServiceRouteManager::get('links', $options);

        return $this->post($route, [
            'platform' => $this->platform,
            'key' => $this->key,
            'file_paths' => $paths,
            'download_names' => $downloadNames,
            'expires' => $expires,
            // Do not check if file exists
            'file_check_mode' => 3,
            // Currently only used for image processing
            'options' => [
                'image' => $options['image'] ?? [],
                'internal_endpoint' => (bool) ($options['internal_endpoint'] ?? false),
                'use_cdn' => (bool) ($options['use_cdn'] ?? false),
            ],
        ], $options);
    }

    public function destroy(array $paths, array $options = []): void
    {
        $route = FileServiceRouteManager::get('destroy', $options);

        $this->post($route, [
            'platform' => $this->platform,
            'key' => $this->key,
            'file_paths' => $paths,
        ], $options);
    }

    public function copy(string $sourcePath, string $toPath, array $options = []): string
    {
        $route = FileServiceRouteManager::get('copy', $options);

        $data = $this->post($route, [
            'platform' => $this->platform,
            'key' => $this->key,
            'source_file_path' => $sourcePath,
            'destination_file_path' => $toPath,
        ], $options);

        return $data['platform_path'] ?? $toPath;
    }

    private function post(string $path, array $body, array $options): array
    {
        $headers = [
            'request-id' => uniqid($this->sdkContainer->getConfig()->getSdkName() . '_'),
        ];
        $token = $options['token'] ?? '';
        if (empty($token)) {
            $this->sdkContainer->getExceptionBuilder()->throw(401, 'Token is required');
        }
        $headers['token'] = $token;
        if (! empty($options['organization-code'])) {
            $headers['organization-code'] = $options['organization-code'];
        }
        $response = $this->sdkContainer->getClientRequest()->request(RequestMethod::Post, $this->host . $path, [
            RequestOptions::HEADERS => $headers,
            RequestOptions::JSON => $body,
        ]);
        $result = json_decode($response->getBody()->getContents(), true);
        if ($result === false) {
            $this->sdkContainer->getExceptionBuilder()->throw(500, 'Response data parse error');
        }
        if (! isset($result['code']) || $result['code'] !== 1000) {
            $this->sdkContainer->getExceptionBuilder()->throw((int) $result['code'], $result['message'] ?? 'Unknown error');
        }

        return $result['data'] ?? [];
    }
}
