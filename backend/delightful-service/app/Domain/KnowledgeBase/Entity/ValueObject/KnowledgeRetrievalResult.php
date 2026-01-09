<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

use App\Infrastructure\Core\AbstractValueObject;

/**
 * knowledge baseretrieveresultvalueobject.
 *
 * 统onetable示fromdifferentretrievemethod(semanticretrieve,all文retrieve,graphretrieveetc)returnknowledgeslicesegment
 */
class KnowledgeRetrievalResult extends AbstractValueObject
{
    /**
     * semanticretrievetype.
     */
    public const string TYPE_SEMANTIC = 'semantic';

    /**
     * all文retrievetype.
     */
    public const string TYPE_FULLTEXT = 'fulltext';

    /**
     * graphretrievetype.
     */
    public const string TYPE_GRAPH = 'graph';

    /**
     * hybridretrievetype.
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
     * businessID.
     */
    private string $businessId = '';

    /**
     * yuandata.
     */
    private array $metadata = [];

    /**
     * type(semantic, fulltext, graph, hybridetc).
     */
    private string $type = self::TYPE_SEMANTIC;

    private float $score = 0;

    /**
     * fromknowledge baseslicesegment实bodycreateretrieveresult.
     *
     * @param string $id 唯oneidentifier
     * @param string $content content
     * @param string $businessId businessID
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
     * fromgraphdatacreateretrieveresult.
     *
     * @param string $id 唯oneidentifier
     * @param string $content content
     * @param string $businessId businessID
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
     * createemptyretrieveresult.
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
     * getbusinessID.
     */
    public function getBusinessId(): string
    {
        return $this->businessId;
    }

    /**
     * setbusinessID.
     *
     * @param string $businessId businessID
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
     * checkwhetherforsemanticretrievetype.
     */
    public function isSemantic(): bool
    {
        return $this->type === self::TYPE_SEMANTIC;
    }

    /**
     * checkwhetherforall文retrievetype.
     */
    public function isFulltext(): bool
    {
        return $this->type === self::TYPE_FULLTEXT;
    }

    /**
     * checkwhetherforgraphretrievetype.
     */
    public function isGraph(): bool
    {
        return $this->type === self::TYPE_GRAPH;
    }

    /**
     * checkwhetherforhybridretrievetype.
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
