<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\KnowledgeBase\DTO\Request;

use App\Domain\KnowledgeBase\Entity\ValueObject\FragmentConfig;
use App\Infrastructure\Core\AbstractRequestDTO;
use App\Interfaces\KnowledgeBase\DTO\DocumentFile\AbstractDocumentFileDTO;
use App\Interfaces\KnowledgeBase\DTO\DocumentFile\DocumentFileDTOInterface;

class CreateDocumentRequestDTO extends AbstractRequestDTO
{
    public string $knowledgeBaseCode;

    public bool $enabled;

    public array $docMetadata = [];

    public ?FragmentConfig $fragmentConfig = null;

    public DocumentFileDTOInterface $documentFile;

    public static function getHyperfValidationRules(): array
    {
        return [
            'knowledge_base_code' => 'required|string|max:64',
            'enabled' => 'required|boolean',
            'doc_metadata' => 'array',
            'document_file' => 'required|array',
            'document_file.name' => 'required|string|max:255',
            'document_file.key' => 'required|string|max:255',
        ];
    }

    public static function getHyperfValidationMessage(): array
    {
        return [
            'knowledge_base_code.required' => 'knowledge baseencodingcannotfornull',
            'knowledge_base_code.max' => 'knowledge baseencodinglengthcannot超pass64character',
            'name.required' => 'documentnamecannotfornull',
            'name.max' => 'documentnamelengthcannot超pass255character',
            'doc_type.required' => 'documenttypecannotfornull',
            'doc_type.integer' => 'documenttypemustfor整数',
            'doc_type.min' => 'documenttypemustgreater thanequal0',
        ];
    }

    public function getKnowledgeBaseCode(): string
    {
        return $this->knowledgeBaseCode;
    }

    public function setKnowledgeBaseCode(string $knowledgeBaseCode): self
    {
        $this->knowledgeBaseCode = $knowledgeBaseCode;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getDocMetadata(): array
    {
        return $this->docMetadata;
    }

    public function setDocMetadata(array $docMetadata): self
    {
        $this->docMetadata = $docMetadata;
        return $this;
    }

    public function getDocumentFile(): ?DocumentFileDTOInterface
    {
        return $this->documentFile;
    }

    public function setDocumentFile(array|DocumentFileDTOInterface $documentFile): void
    {
        is_array($documentFile) && $documentFile = AbstractDocumentFileDTO::fromArray($documentFile);
        $this->documentFile = $documentFile;
    }

    public function getFragmentConfig(): ?FragmentConfig
    {
        return $this->fragmentConfig;
    }

    public function setFragmentConfig(null|array|FragmentConfig $fragmentConfig): static
    {
        is_array($fragmentConfig) && $fragmentConfig = FragmentConfig::fromArray($fragmentConfig);
        $this->fragmentConfig = $fragmentConfig;
        return $this;
    }
}
