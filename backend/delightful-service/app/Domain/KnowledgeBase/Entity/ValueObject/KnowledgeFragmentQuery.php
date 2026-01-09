<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

class KnowledgeFragmentQuery
{
    public string $knowledgeCode = '';

    public array $metadataFilter = [];

    public int $limit = 5;

    /**
     * getknowledge baseencoding
     */
    public function getKnowledgeCode(): string
    {
        return $this->knowledgeCode;
    }

    /**
     * settingknowledge baseencoding
     */
    public function setKnowledgeCode(string $knowledgeCode): void
    {
        $this->knowledgeCode = $knowledgeCode;
    }

    /**
     * getyuandatafilteritemitem.
     */
    public function getMetadataFilter(): array
    {
        return $this->metadataFilter;
    }

    /**
     * settingyuandatafilteritemitem.
     */
    public function setMetadataFilter(array $metadataFilter): void
    {
        $this->metadataFilter = $metadataFilter;
    }

    /**
     * get限制quantity.
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * setting限制quantity.
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }
}
