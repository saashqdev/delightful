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
 * contain重sortmodel相closeconfigurationparameter,likemodelname,providequotient,API clientpointetc
 */
class RerankingModelConfig extends AbstractValueObject
{
    /**
     * 重sortmodelname.
     *
     * for example:BAAI/bge-reranker-large
     */
    private string $rerankingModelName = '';

    /**
     * 重sortmodelprovidequotientname.
     *
     * for example:gitee_ai,openai etc
     */
    private string $rerankingProviderName = '';

    /**
     * API clientpoint.
     *
     * 重sortservice API clientpoint
     */
    private string $apiEndpoint = '';

    /**
     * API key.
     *
     * access重sortservice API key
     */
    private string $apiKey = '';

    /**
     * timeouttime(second).
     *
     * API requesttimeouttime,unitforsecond
     */
    private float $timeout = 3.0;

    /**
     * retrycount.
     *
     * API requestfailo clockretrycount
     */
    private int $retryCount = 2;

    /**
     * returnmostbigresultquantity.
     *
     * 重sortbackreturnmostbigresultquantity
     */
    private int $topN = 3;

    /**
     * batchprocesssize.
     *
     * batchquantityprocessdocumentsize,useatimproveperformance
     */
    private int $batchSize = 16;

    /**
     * whetherusecache.
     *
     * whethercache重sortresult,useatimproveperformance
     */
    private bool $useCache = true;

    /**
     * cacheexpiretime(second).
     *
     * cacheexpiretime,unitforsecond
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
     * get重sortmodelprovidequotientname.
     */
    public function getRerankingProviderName(): string
    {
        return $this->rerankingProviderName;
    }

    /**
     * set重sortmodelprovidequotientname.
     */
    public function setRerankingProviderName(string $rerankingProviderName): self
    {
        $this->rerankingProviderName = $rerankingProviderName;
        return $this;
    }

    /**
     * get API clientpoint.
     */
    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    /**
     * set API clientpoint.
     */
    public function setApiEndpoint(string $apiEndpoint): self
    {
        $this->apiEndpoint = $apiEndpoint;
        return $this;
    }

    /**
     * get API key.
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * set API key.
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * gettimeouttime.
     */
    public function getTimeout(): float
    {
        return $this->timeout;
    }

    /**
     * settimeouttime.
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
     * getretrycount.
     */
    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    /**
     * setretrycount.
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
     * getreturnmostbigresultquantity.
     */
    public function getTopN(): int
    {
        return $this->topN;
    }

    /**
     * setreturnmostbigresultquantity.
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
     * getbatchprocesssize.
     */
    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * setbatchprocesssize.
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
     * whetherusecache.
     */
    public function isUseCache(): bool
    {
        return $this->useCache;
    }

    /**
     * setwhetherusecache.
     */
    public function setUseCache(bool $useCache): self
    {
        $this->useCache = $useCache;
        return $this;
    }

    /**
     * getcacheexpiretime.
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * setcacheexpiretime.
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
     * createdefaultconfiguration.
     */
    public static function createDefault(): self
    {
        return new self();
    }

    /**
     * fromarraycreateconfiguration.
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
     * convertforarray.
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
