<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

use App\Infrastructure\Core\AbstractValueObject;
use InvalidArgumentException;

/**
 * 图searchconfigurationvalueobject
 *
 * contain图search的相关configurationparameter，如 API 端点、authinfo、timeoutset等
 */
class GraphSearchConfig extends AbstractValueObject
{
    /**
     * 关系权重.
     *
     * 图search中关系的权重，用于计算final相似度分数
     */
    private float $relationWeight = 0.5;

    /**
     * 最大search深度.
     *
     * 图search的最大深度，即从起始节点开始，at mostsearch几跳关系
     */
    private int $maxDepth = 2;

    /**
     * 是否containproperty.
     *
     * 是否在searchresult中contain节点和关系的property
     */
    private bool $includeProperties = true;

    /**
     * API 端点.
     *
     * 图searchservice的 API 端点
     */
    private string $apiEndpoint = '';

    /**
     * API key.
     *
     * access图searchservice的 API key
     */
    private string $apiKey = '';

    /**
     * timeouttime（秒）.
     *
     * API request的timeouttime，单位为秒
     */
    private float $timeout = 5.0;

    /**
     * retry次数.
     *
     * API requestfail时的retry次数
     */
    private int $retryCount = 3;

    /**
     * 关系type.
     *
     * search时考虑的关系typelist，为空table示所有type
     */
    private array $relationTypes = [];

    /**
     * 节点type.
     *
     * search时考虑的节点typelist，为空table示所有type
     */
    private array $nodeTypes = [];

    /**
     * result限制.
     *
     * return的最大resultquantity
     */
    private int $limit = 10;

    /**
     * get关系权重.
     */
    public function getRelationWeight(): float
    {
        return $this->relationWeight;
    }

    /**
     * set关系权重.
     */
    public function setRelationWeight(float $relationWeight): self
    {
        if ($relationWeight < 0 || $relationWeight > 1) {
            throw new InvalidArgumentException('Relation weight must be between 0 and 1');
        }
        $this->relationWeight = $relationWeight;
        return $this;
    }

    /**
     * get最大search深度.
     */
    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    /**
     * set最大search深度.
     */
    public function setMaxDepth(int $maxDepth): self
    {
        if ($maxDepth < 1) {
            throw new InvalidArgumentException('Max depth must be greater than 0');
        }
        $this->maxDepth = $maxDepth;
        return $this;
    }

    /**
     * 是否containproperty.
     */
    public function isIncludeProperties(): bool
    {
        return $this->includeProperties;
    }

    /**
     * set是否containproperty.
     */
    public function setIncludeProperties(bool $includeProperties): self
    {
        $this->includeProperties = $includeProperties;
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
     * getretry次数.
     */
    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    /**
     * setretry次数.
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
     * get关系type.
     */
    public function getRelationTypes(): array
    {
        return $this->relationTypes;
    }

    /**
     * set关系type.
     */
    public function setRelationTypes(array $relationTypes): self
    {
        $this->relationTypes = $relationTypes;
        return $this;
    }

    /**
     * get节点type.
     */
    public function getNodeTypes(): array
    {
        return $this->nodeTypes;
    }

    /**
     * set节点type.
     */
    public function setNodeTypes(array $nodeTypes): self
    {
        $this->nodeTypes = $nodeTypes;
        return $this;
    }

    /**
     * getresult限制.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * setresult限制.
     */
    public function setLimit(int $limit): self
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('Limit must be greater than 0');
        }
        $this->limit = $limit;
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
     * 从arraycreateconfiguration.
     */
    public static function fromArray(array $config): self
    {
        $graphSearchConfig = new self();

        if (isset($config['relation_weight'])) {
            $graphSearchConfig->setRelationWeight($config['relation_weight']);
        }

        if (isset($config['max_depth'])) {
            $graphSearchConfig->setMaxDepth($config['max_depth']);
        }

        if (isset($config['include_properties'])) {
            $graphSearchConfig->setIncludeProperties($config['include_properties']);
        }

        if (isset($config['api_endpoint'])) {
            $graphSearchConfig->setApiEndpoint($config['api_endpoint']);
        }

        if (isset($config['api_key'])) {
            $graphSearchConfig->setApiKey($config['api_key']);
        }

        if (isset($config['timeout'])) {
            $graphSearchConfig->setTimeout($config['timeout']);
        }

        if (isset($config['retry_count'])) {
            $graphSearchConfig->setRetryCount($config['retry_count']);
        }

        if (isset($config['relation_types'])) {
            $graphSearchConfig->setRelationTypes($config['relation_types']);
        }

        if (isset($config['node_types'])) {
            $graphSearchConfig->setNodeTypes($config['node_types']);
        }

        if (isset($config['limit'])) {
            $graphSearchConfig->setLimit($config['limit']);
        }

        return $graphSearchConfig;
    }

    /**
     * 转换为array.
     */
    public function toArray(): array
    {
        return [
            'relation_weight' => $this->relationWeight,
            'max_depth' => $this->maxDepth,
            'include_properties' => $this->includeProperties,
            'api_endpoint' => $this->apiEndpoint,
            'api_key' => $this->apiKey,
            'timeout' => $this->timeout,
            'retry_count' => $this->retryCount,
            'relation_types' => $this->relationTypes,
            'node_types' => $this->nodeTypes,
            'limit' => $this->limit,
        ];
    }
}
