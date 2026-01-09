<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

use App\Infrastructure\Core\AbstractValueObject;
use InvalidArgumentException;

/**
 * 重sortmodelconfigurationvalueobject
 *
 * 包含重sortmodel的相关configurationparameter，如modelname、提供商、API 端点等
 */
class RerankingModelConfig extends AbstractValueObject
{
    /**
     * 重sortmodelname.
     *
     * for example：BAAI/bge-reranker-large
     */
    private string $rerankingModelName = '';

    /**
     * 重sortmodel提供商name.
     *
     * for example：gitee_ai、openai 等
     */
    private string $rerankingProviderName = '';

    /**
     * API 端点.
     *
     * 重sortservice的 API 端点
     */
    private string $apiEndpoint = '';

    /**
     * API 密钥.
     *
     * 访问重sortservice的 API 密钥
     */
    private string $apiKey = '';

    /**
     * 超时time（秒）.
     *
     * API 请求的超时time，单位为秒
     */
    private float $timeout = 3.0;

    /**
     * 重试次数.
     *
     * API 请求fail时的重试次数
     */
    private int $retryCount = 2;

    /**
     * return的最大result数量.
     *
     * 重sort后return的最大result数量
     */
    private int $topN = 3;

    /**
     * 批处理大小.
     *
     * 批量处理文档的大小，用于提高性能
     */
    private int $batchSize = 16;

    /**
     * 是否usecache.
     *
     * 是否cache重sortresult，用于提高性能
     */
    private bool $useCache = true;

    /**
     * cache过期time（秒）.
     *
     * cache的过期time，单位为秒
     */
    private int $cacheTtl = 3600;

    /**
     * get重sortmodelname.
     */
    public function getRerankingModelName(): string
    {
        return $this->rerankingModelName;
    }

    /**
     * set重sortmodelname.
     */
    public function setRerankingModelName(string $rerankingModelName): self
    {
        $this->rerankingModelName = $rerankingModelName;
        return $this;
    }

    /**
     * get重sortmodel提供商name.
     */
    public function getRerankingProviderName(): string
    {
        return $this->rerankingProviderName;
    }

    /**
     * set重sortmodel提供商name.
     */
    public function setRerankingProviderName(string $rerankingProviderName): self
    {
        $this->rerankingProviderName = $rerankingProviderName;
        return $this;
    }

    /**
     * get API 端点.
     */
    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    /**
     * set API 端点.
     */
    public function setApiEndpoint(string $apiEndpoint): self
    {
        $this->apiEndpoint = $apiEndpoint;
        return $this;
    }

    /**
     * get API 密钥.
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * set API 密钥.
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * get超时time.
     */
    public function getTimeout(): float
    {
        return $this->timeout;
    }

    /**
     * set超时time.
     */
    public function setTimeout(float $timeout): self
    {
        if ($timeout <= 0) {
            throw new InvalidArgumentException('Timeout must be greater than 0');
        }
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * get重试次数.
     */
    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    /**
     * set重试次数.
     */
    public function setRetryCount(int $retryCount): self
    {
        if ($retryCount < 0) {
            throw new InvalidArgumentException('Retry count must be greater than or equal to 0');
        }
        $this->retryCount = $retryCount;
        return $this;
    }

    /**
     * getreturn的最大result数量.
     */
    public function getTopN(): int
    {
        return $this->topN;
    }

    /**
     * setreturn的最大result数量.
     */
    public function setTopN(int $topN): self
    {
        if ($topN < 1) {
            throw new InvalidArgumentException('TopN must be greater than 0');
        }
        $this->topN = $topN;
        return $this;
    }

    /**
     * get批处理大小.
     */
    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * set批处理大小.
     */
    public function setBatchSize(int $batchSize): self
    {
        if ($batchSize < 1) {
            throw new InvalidArgumentException('Batch size must be greater than 0');
        }
        $this->batchSize = $batchSize;
        return $this;
    }

    /**
     * 是否usecache.
     */
    public function isUseCache(): bool
    {
        return $this->useCache;
    }

    /**
     * set是否usecache.
     */
    public function setUseCache(bool $useCache): self
    {
        $this->useCache = $useCache;
        return $this;
    }

    /**
     * getcache过期time.
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * setcache过期time.
     */
    public function setCacheTtl(int $cacheTtl): self
    {
        if ($cacheTtl < 0) {
            throw new InvalidArgumentException('Cache TTL must be greater than or equal to 0');
        }
        $this->cacheTtl = $cacheTtl;
        return $this;
    }

    /**
     * create默认configuration.
     */
    public static function createDefault(): self
    {
        return new self();
    }

    /**
     * 从arraycreateconfiguration.
     */
    public static function fromArray(array $config): self
    {
        $rerankingModelConfig = new self();

        if (isset($config['reranking_model_name'])) {
            $rerankingModelConfig->setRerankingModelName($config['reranking_model_name']);
        }

        if (isset($config['reranking_provider_name'])) {
            $rerankingModelConfig->setRerankingProviderName($config['reranking_provider_name']);
        }

        if (isset($config['api_endpoint'])) {
            $rerankingModelConfig->setApiEndpoint($config['api_endpoint']);
        }

        if (isset($config['api_key'])) {
            $rerankingModelConfig->setApiKey($config['api_key']);
        }

        if (isset($config['timeout'])) {
            $rerankingModelConfig->setTimeout($config['timeout']);
        }

        if (isset($config['retry_count'])) {
            $rerankingModelConfig->setRetryCount($config['retry_count']);
        }

        if (isset($config['top_n'])) {
            $rerankingModelConfig->setTopN($config['top_n']);
        }

        if (isset($config['batch_size'])) {
            $rerankingModelConfig->setBatchSize($config['batch_size']);
        }

        if (isset($config['use_cache'])) {
            $rerankingModelConfig->setUseCache($config['use_cache']);
        }

        if (isset($config['cache_ttl'])) {
            $rerankingModelConfig->setCacheTtl($config['cache_ttl']);
        }

        return $rerankingModelConfig;
    }

    /**
     * 转换为array.
     */
    public function toArray(): array
    {
        return [
            'reranking_model_name' => $this->rerankingModelName,
            'reranking_provider_name' => $this->rerankingProviderName,
            'api_endpoint' => $this->apiEndpoint,
            'api_key' => $this->apiKey,
            'timeout' => $this->timeout,
            'retry_count' => $this->retryCount,
            'top_n' => $this->topN,
            'batch_size' => $this->batchSize,
            'use_cache' => $this->useCache,
            'cache_ttl' => $this->cacheTtl,
        ];
    }
}
