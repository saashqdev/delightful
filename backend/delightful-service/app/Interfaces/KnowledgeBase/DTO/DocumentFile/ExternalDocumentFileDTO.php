<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\KnowledgeBase\DTO\DocumentFile;

use App\Domain\KnowledgeBase\Entity\ValueObject\DocumentFile\DocumentFileType;

class ExternalDocumentFileDTO extends AbstractDocumentFileDTO
{
    public string $key;

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    protected function initType(): DocumentFileType
    {
        return DocumentFileType::EXTERNAL;
    }
}
