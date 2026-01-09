<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\OCR;

use App\Infrastructure\Core\Exception\OCRException;
use Hyperf\Codec\Json;
use Hyperf\Logger\LoggerFactory;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Throwable;

use function Hyperf\Support\retry;

readonly class OCRService
{
    private LoggerInterface $logger;

    public function __construct(
        protected OCRClientFactory $clientFactory,
        protected LoggerFactory $loggerFactory,
        protected CacheInterface $cache,
    ) {
        $this->logger = $loggerFactory->get('ocr_service');
    }

    public function ocr(OCRClientType $type, ?string $url = null): string
    {
        if ($url === null) {
            throw new InvalidArgumentException('url is empty');
        }
        $ocrClient = $this->clientFactory->getClient($type);
        try {
            $result = retry(1, function () use ($ocrClient, $url) {
                // ifalsohave其他servicequotient，这withincan故障转移
                return $this->get($url, $ocrClient);
            }, 1000);
        } catch (Throwable $throwable) {
            $this->logger->warning('ocr_fail', [
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode(),
                'trace' => $throwable->getTraceAsString(),
            ]);
            throw new OCRException($throwable->getMessage(), 500, $throwable);
        }
        return $result;
    }

    private function get(string $url, OCRClientInterface $OCRClient): string
    {
        // definition Redis cache键
        $cacheKey = 'file_cache:' . md5($url);

        // 尝试fromcachegetdata
        $cachedData = $this->cache->get($cacheKey);
        if ($cachedData) {
            $cachedData = Json::decode($cachedData);
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        // get远程fileheadinfo
        $headers = get_headers($url, true, $context);
        if ($headers === false) {
            throw new RuntimeException("no法getheadinfo: {$url}");
        }

        // extract `Last-Modified`、`ETag` and `Content-Length`（if存in）
        $lastModified = $headers['Last-Modified'] ?? null;
        $etag = $headers['Etag'] ?? null;
        $contentLength = $headers['Content-Length'] ?? null;

        // checkcachemiddle `Last-Modified`、`ETag` and `Content-Length`
        if ($cachedData) {
            $isCacheValid = true;

            // check Last-Modified and ETag
            if ($lastModified && $etag) {
                $isCacheValid = $cachedData['Last-Modified'] === $lastModified && $cachedData['Etag'] === $etag;
            }
            // ifnothave Last-Modified or ETag，check Content-Length
            elseif ($contentLength) {
                $isCacheValid = $cachedData['Content-Length'] === $contentLength;
            }

            // ifcachedatavalid，直接returncachecontent
            if ($isCacheValid) {
                return $cachedData['content'];
            }
        }

        // call OCR serviceconductprocess
        $result = $OCRClient->ocr($url);

        // updatecachedata
        $newCacheData = [
            'Last-Modified' => $lastModified,
            'Etag' => $etag,
            'Content-Length' => $contentLength,
            'content' => $result,
        ];

        $this->cache->set($cacheKey, Json::encode($newCacheData), 1800);

        return $result;
    }
}
