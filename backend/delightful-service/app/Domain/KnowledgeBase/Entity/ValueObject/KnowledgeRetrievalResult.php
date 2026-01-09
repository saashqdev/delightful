<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

use App\Infrastructure\Core\AbstractValueObject;

/**
 * 知识库检索resultvalueobject.
 *
 * 统一table示从different检索method（语义检索、全文检索、图检索等）return的知识片段
 */
class KnowledgeRetrievalResult extends AbstractValueObject
{
    /**
     * 语义检索type.
     */
    public const string TYPE_SEMANTIC = 'semantic';

    /**
     * 全文检索type.
     */
    public const string TYPE_FULLTEXT = 'fulltext';

    /**
     * 图检索type.
     */
    public const string TYPE_GRAPH = 'graph';

    /**
     * 混合检索type.
     */
    public const string TYPE_HYBRID = 'hybrid';

    /**
     * 唯一标识符.
     */
    private string $id = '';

    /**
     * content.
     */
    private string $content = '';

    /**
     * 业务ID.
     */
    private string $businessId = '';

    /**
     * 元数据.
     */
    private array $metadata = [];

    /**
     * type（semantic, fulltext, graph, hybrid等）.
     */
    private string $type = self::TYPE_SEMANTIC;

    private float $score = 0;

    /**
     * 从知识库片段实体create检索result.
     *
     * @param string $id 唯一标识符
     * @param string $content content
     * @param string $businessId 业务ID
     * @param array $metadata 元数据
     */
    public static function fromFragment(
        string $id = '',
        string $content = '',
        string $businessId = '',
        array $metadata = [],
        float $score = 0,
    ): self {
        $instance = new self();
        $instance->setId($id);
        $instance->setContent($content);
        $instance->setBusinessId($businessId);
        $instance->setMetadata($metadata);
        $instance->setType(self::TYPE_SEMANTIC);
        $instance->setScore($score);

        return $instance;
    }

    /**
     * 从图数据create检索result.
     *
     * @param string $id 唯一标识符
     * @param string $content content
     * @param string $businessId 业务ID
     * @param array $metadata 元数据
     */
    public static function fromGraphData(
        string $id = '',
        string $content = '',
        string $businessId = '',
        array $metadata = []
    ): self {
        $instance = new self();
        $instance->setId($id);
        $instance->setContent($content);
        $instance->setBusinessId($businessId);
        $instance->setMetadata($metadata);
        $instance->setType(self::TYPE_GRAPH);

        return $instance;
    }

    /**
     * create空的检索result.
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * get唯一标识符.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * set唯一标识符.
     *
     * @param string $id 唯一标识符
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * getcontent.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * setcontent.
     *
     * @param string $content content
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * get业务ID.
     */
    public function getBusinessId(): string
    {
        return $this->businessId;
    }

    /**
     * set业务ID.
     *
     * @param string $businessId 业务ID
     */
    public function setBusinessId(string $businessId): self
    {
        $this->businessId = $businessId;
        return $this;
    }

    /**
     * get元数据.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * set元数据.
     *
     * @param array $metadata 元数据
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * gettype.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * settype.
     *
     * @param string $type type
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * check是否为语义检索type.
     */
    public function isSemantic(): bool
    {
        return $this->type === self::TYPE_SEMANTIC;
    }

    /**
     * check是否为全文检索type.
     */
    public function isFulltext(): bool
    {
        return $this->type === self::TYPE_FULLTEXT;
    }

    /**
     * check是否为图检索type.
     */
    public function isGraph(): bool
    {
        return $this->type === self::TYPE_GRAPH;
    }

    /**
     * check是否为混合检索type.
     */
    public function isHybrid(): bool
    {
        return $this->type === self::TYPE_HYBRID;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): static
    {
        $this->score = $score;
        return $this;
    }
}
