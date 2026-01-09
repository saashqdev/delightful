<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

use App\Infrastructure\Core\AbstractValueObject;
use InvalidArgumentException;

/**
 * graphsearchconfigurationvalueobject
 *
 * containgraphsearch相closeconfigurationparameter,如 API 端point、authinfo、timeoutsetetc
 */
class GraphSearchConfig extends AbstractValueObject
{
    /**
     * close系weight.
     *
     * graphsearchmiddleclose系weight,useatcalculatefinalsimilardegreeminute数
     */
    private float $relationWeight = 0.5;

    /**
     * mostbigsearch深degree.
     *
     * graphsearchmostbig深degree,即fromup始sectionpointstart,at mostsearch几跳close系
     */
    private int $maxDepth = 2;

    /**
     * whethercontainproperty.
     *
     * whetherinsearchresultmiddlecontainsectionpointandclose系property
     */
    private bool $includeProperties = true;

    /**
     * API 端point.
     *
     * graphsearchservice API 端point
     */
    private string $apiEndpoint = '';

    /**
     * API key.
     *
     * accessgraphsearchservice API key
     */
    private string $apiKey = '';

    /**
     * timeouttime(second).
     *
     * API requesttimeouttime,unitforsecond
     */
    private float $timeout = 5.0;

    /**
     * retrycount.
     *
     * API requestfailo clockretrycount
     */
    private int $retryCount = 3;

    /**
     * close系type.
     *
     * searcho clock考虑close系typelist,foremptytable示所havetype
     */
    private array $relationTypes = [];

    /**
     * sectionpointtype.
     *
     * searcho clock考虑sectionpointtypelist,foremptytable示所havetype
     */
    private array $nodeTypes = [];

    /**
     * resultlimit.
     *
     * returnmostbigresultquantity
     */
    private int $limit = 10;

    /**
     * getclose系weight.
     */
    public function getRelationWeight(): float
    {
        return $this->relationWeight;
    }

    /**
     * setclose系weight.
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
     * getmostbigsearch深degree.
     */
    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    /**
     * setmostbigsearch深degree.
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
     * whethercontainproperty.
     */
    public function isIncludeProperties(): bool
    {
        return $this->includeProperties;
    }

    /**
     * setwhethercontainproperty.
     */
    public function setIncludeProperties(bool $includeProperties): self
    {
        $this->includeProperties = $includeProperties;
        return $this;
    }

    /**
     * get API 端point.
     */
    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    /**
     * set API 端point.
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
     * getclose系type.
     */
    public function getRelationTypes(): array
    {
        return $this->relationTypes;
    }

    /**
     * setclose系type.
     */
    public function setRelationTypes(array $relationTypes): self
    {
        $this->relationTypes = $relationTypes;
        return $this;
    }

    /**
     * getsectionpointtype.
     */
    public function getNodeTypes(): array
    {
        return $this->nodeTypes;
    }

    /**
     * setsectionpointtype.
     */
    public function setNodeTypes(array $nodeTypes): self
    {
        $this->nodeTypes = $nodeTypes;
        return $this;
    }

    /**
     * getresultlimit.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * setresultlimit.
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
     * fromarraycreateconfiguration.
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
     * convertforarray.
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
