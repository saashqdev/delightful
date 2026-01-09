<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

use App\Infrastructure\Core\AbstractValueObject;
use InvalidArgumentException;

/**
 * knowledge base检索configurationvalueobject
 *
 * contain检索strategy、检索method、重sortconfigurationetcparameter
 */
class RetrieveConfig extends AbstractValueObject
{
    /**
     * currentconfigurationversion.
     *
     * useatconfiguration结构变moreo clock的compatiblepropertyprocess
     */
    public const int CURRENT_VERSION = 1;

    /**
     * 检索method.
     *
     * optionalvalue：
     * - semantic_search: 语义检索
     * - full_text_search: all文检索
     * - hybrid_search: 混合检索
     * - graph_search: 图检索
     *
     * @see RetrievalMethod
     */
    protected string $searchMethod = RetrievalMethod::SEMANTIC_SEARCH;

    /**
     * return的most大resultquantity.
     */
    protected int $topK = 3;

    /**
     * minute数阈value
     *
     * 只returnsimilardegreeminute数greater than该阈value的result
     */
    protected float $scoreThreshold = 0.5;

    /**
     * whetherenableminute数阈valuefilter.
     */
    protected bool $scoreThresholdEnabled = false;

    /**
     * 重sort模type.
     *
     * optionalvalue：
     * - reranking_model: use重sortmodel
     * - weighted_score: use加权minute数
     *
     * @see RerankMode
     */
    protected string $rerankingMode = RerankMode::WEIGHTED_SCORE;

    /**
     * whetherenable重sort.
     */
    protected bool $rerankingEnable = false;

    /**
     * 权重configuration.
     *
     * containtoquantity检索和keyword检索的权重configuration
     */
    protected array $weights = [
        'vector_setting' => [
            'vector_weight' => 1.0,
            'embedding_model_name' => '',
            'embedding_provider_name' => '',
        ],
        'keyword_setting' => [
            'keyword_weight' => 0.0,
        ],
        'graph_setting' => [
            'relation_weight' => 0.5,
            'max_depth' => 2,
            'include_properties' => true,
            'timeout' => 5.0,
            'retry_count' => 3,
        ],
    ];

    /**
     * 重sortmodelconfiguration.
     *
     * contain重sortmodel的相关configurationparameter
     */
    protected array $rerankingModel = [
        'reranking_model_name' => '',
        'reranking_provider_name' => '',
    ];

    /**
     * configurationversion.
     *
     * useatconfiguration结构变moreo clock的compatiblepropertyprocess
     */
    private int $version = self::CURRENT_VERSION;

    /**
     * getconfigurationversion.
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * setconfigurationversion.
     */
    public function setVersion(int $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * get检索method.
     */
    public function getSearchMethod(): string
    {
        return $this->searchMethod;
    }

    /**
     * set检索method.
     */
    public function setSearchMethod(string $searchMethod): self
    {
        if (! RetrievalMethod::isValid($searchMethod)) {
            throw new InvalidArgumentException("Invalid search method: {$searchMethod}");
        }
        $this->searchMethod = $searchMethod;
        return $this;
    }

    /**
     * getreturn的most大resultquantity.
     */
    public function getTopK(): int
    {
        return $this->topK;
    }

    /**
     * setreturn的most大resultquantity.
     */
    public function setTopK(int $topK): self
    {
        if ($topK < 1) {
            throw new InvalidArgumentException('TopK must be greater than 0');
        }
        $this->topK = $topK;
        return $this;
    }

    /**
     * getminute数阈value
     */
    public function getScoreThreshold(): float
    {
        return $this->scoreThreshold;
    }

    /**
     * setminute数阈value
     */
    public function setScoreThreshold(float $scoreThreshold): self
    {
        if ($scoreThreshold < 0 || $scoreThreshold > 1) {
            throw new InvalidArgumentException('Score threshold must be between 0 and 1');
        }
        $this->scoreThreshold = $scoreThreshold;
        return $this;
    }

    /**
     * whetherenableminute数阈valuefilter.
     */
    public function isScoreThresholdEnabled(): bool
    {
        return $this->scoreThresholdEnabled;
    }

    /**
     * setwhetherenableminute数阈valuefilter.
     */
    public function setScoreThresholdEnabled(bool $scoreThresholdEnabled): self
    {
        $this->scoreThresholdEnabled = $scoreThresholdEnabled;
        return $this;
    }

    /**
     * get重sort模type.
     */
    public function getRerankingMode(): string
    {
        return $this->rerankingMode;
    }

    /**
     * set重sort模type.
     */
    public function setRerankingMode(string $rerankingMode): self
    {
        if (! RerankMode::isValid($rerankingMode)) {
            throw new InvalidArgumentException("Invalid reranking mode: {$rerankingMode}");
        }
        $this->rerankingMode = $rerankingMode;
        return $this;
    }

    /**
     * whetherenable重sort.
     */
    public function isRerankingEnable(): bool
    {
        return $this->rerankingEnable;
    }

    /**
     * setwhetherenable重sort.
     */
    public function setRerankingEnable(bool $rerankingEnable): self
    {
        $this->rerankingEnable = $rerankingEnable;
        return $this;
    }

    /**
     * get权重configuration.
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * set权重configuration.
     */
    public function setWeights(array $weights): self
    {
        // verify权重configuration
        if (! isset($weights['vector_setting']) || ! isset($weights['keyword_setting']) || ! isset($weights['graph_setting'])) {
            throw new InvalidArgumentException('Weights must contain vector_setting, keyword_setting and graph_setting');
        }

        if (! isset($weights['vector_setting']['vector_weight'])
            || ! isset($weights['keyword_setting']['keyword_weight'])) {
            throw new InvalidArgumentException('Vector setting must contain vector_weight and keyword setting must contain keyword_weight');
        }

        // verify graph_setting mustcontain必要的field
        if (! isset($weights['graph_setting']['relation_weight'])
            || ! isset($weights['graph_setting']['max_depth'])
            || ! isset($weights['graph_setting']['include_properties'])) {
            throw new InvalidArgumentException('Graph setting must contain relation_weight, max_depth and include_properties');
        }

        $vectorWeight = $weights['vector_setting']['vector_weight'];
        $keywordWeight = $weights['keyword_setting']['keyword_weight'];

        if ($vectorWeight < 0 || $vectorWeight > 1
            || $keywordWeight < 0 || $keywordWeight > 1) {
            throw new InvalidArgumentException('Weights must be between 0 and 1');
        }

        $this->weights = $weights;
        return $this;
    }

    /**
     * get重sortmodelconfiguration.
     */
    public function getRerankingModel(): array
    {
        return $this->rerankingModel;
    }

    /**
     * set重sortmodelconfiguration.
     */
    public function setRerankingModel(array $rerankingModel): self
    {
        // mergedefaultconfiguration
        $this->rerankingModel = array_merge($this->rerankingModel, $rerankingModel);
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
        $retrieveConfig = new self();
        if (isset($config['search_method'])) {
            $retrieveConfig->setSearchMethod($config['search_method']);
        }

        if (isset($config['top_k'])) {
            $retrieveConfig->setTopK($config['top_k']);
        }

        if (isset($config['score_threshold'])) {
            $retrieveConfig->setScoreThreshold($config['score_threshold']);
        }

        if (isset($config['score_threshold_enabled'])) {
            $retrieveConfig->setScoreThresholdEnabled($config['score_threshold_enabled']);
        }

        if (isset($config['reranking_mode'])) {
            $retrieveConfig->setRerankingMode($config['reranking_mode']);
        }

        if (isset($config['reranking_enable'])) {
            $retrieveConfig->setRerankingEnable($config['reranking_enable']);
        }

        if (isset($config['weights'])) {
            $retrieveConfig->setWeights($config['weights']);
        }

        if (isset($config['reranking_model'])) {
            $retrieveConfig->setRerankingModel($config['reranking_model']);
        }

        return $retrieveConfig;
    }

    /**
     * convert为array.
     */
    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'search_method' => $this->searchMethod,
            'top_k' => $this->topK,
            'score_threshold' => $this->scoreThreshold,
            'score_threshold_enabled' => $this->scoreThresholdEnabled,
            'reranking_mode' => $this->rerankingMode,
            'reranking_enable' => $this->rerankingEnable,
            'weights' => $this->weights,
            'reranking_model' => $this->rerankingModel,
        ];
    }
}
