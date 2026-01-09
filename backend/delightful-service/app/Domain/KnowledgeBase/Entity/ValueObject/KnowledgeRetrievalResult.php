<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

use App\Infrastructure\Core\AbstractValueObject;

/**
 * knowledge base检索resultvalueobject.
 *
 * 统onetable示fromdifferent检索method（语义检索、all文检索、graph检索etc）return知识slicesegment
 */
class KnowledgeRetrievalResult extends AbstractValueObject
{
    /**
     * 语义检索type.
     */
    public const string TYPE_SEMANTIC = 'semantic';

    /**
     * all文检索type.
     */
    public const string TYPE_FULLTEXT = 'fulltext';

    /**
     * graph检索type.
     */
    public const string TYPE_GRAPH = 'graph';

    /**
     * 混合检索type.
     */
    public const string TYPE_HYBRID = 'hybrid';

    /**
     * 唯oneidentifier.
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
     * yuandata.
     */
    private array $metadata = [];

    /**
     * type（semantic, fulltext, graph, hybridetc）.
     */
    private string $type = self::TYPE_SEMANTIC;

    private float $score = 0;

    /**
     * fromknowledge baseslicesegment实bodycreate检索result.
     *
     * @param string $id 唯oneidentifier
     * @param string $content content
     * @param string $businessId 业务ID
     * @param array $metadata yuandata
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
     * fromgraphdatacreate检索result.
     *
     * @param string $id 唯oneidentifier
     * @param string $content content
     * @param string $businessId 业务ID
     * @param array $metadata yuandata
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
     * createempty检索result.
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * get唯oneidentifier.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * set唯oneidentifier.
     *
     * @param string $id 唯oneidentifier
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
     * getyuandata.
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * setyuandata.
     *
     * @param array $metadata yuandata
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
     * checkwhetherfor语义检索type.
     */
    public function isSemantic(): bool
    {
        return $this->type === self::TYPE_SEMANTIC;
    }

    /**
     * checkwhetherforall文检索type.
     */
    public function isFulltext(): bool
    {
        return $this->type === self::TYPE_FULLTEXT;
    }

    /**
     * checkwhetherforgraph检索type.
     */
    public function isGraph(): bool
    {
        return $this->type === self::TYPE_GRAPH;
    }

    /**
     * checkwhetherfor混合检索type.
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
