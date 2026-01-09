<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\KnowledgeBase\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

class UpdateFragmentRequestDTO extends AbstractRequestDTO
{
    public int $id;

    public string $knowledgeBaseCode;

    public string $documentCode;

    public string $content;

    public array $metadata = [];

    public static function getHyperfValidationRules(): array
    {
        return [
            'id' => 'required|integer',
            'knowledge_base_code' => 'required|string|max:255',
            'document_code' => 'required|string|max:255',
            'content' => 'required|string|max:65535',
            'metadata' => 'array',
        ];
    }

    public static function getHyperfValidationMessage(): array
    {
        return [
            'id.required' => 'knowledge baseencodingcannot为null',
            'knowledge_base_code.required' => 'knowledge baseencodingcannot为null',
            'knowledge_base_code.max' => 'knowledge baseencodinglengthcannot超过255character',
            'document_code.required' => 'documentencodingcannot为null',
            'document_code.max' => 'documentencodinglengthcannot超过255character',
            'content.required' => 'slicesegmentcontentcannot为null',
            'content.max' => 'slicesegmentcontentlengthcannot超过65535character',
        ];
    }

    public function getKnowledgeBaseCode(): string
    {
        return $this->knowledgeBaseCode;
    }

    public function setKnowledgeBaseCode(string $knowledgeBaseCode): UpdateFragmentRequestDTO
    {
        $this->knowledgeBaseCode = $knowledgeBaseCode;
        return $this;
    }

    public function getDocumentCode(): string
    {
        return $this->documentCode;
    }

    public function setDocumentCode(string $documentCode): UpdateFragmentRequestDTO
    {
        $this->documentCode = $documentCode;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): UpdateFragmentRequestDTO
    {
        $this->content = $content;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): UpdateFragmentRequestDTO
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int|string $id): UpdateFragmentRequestDTO
    {
        $this->id = (int) $id;
        return $this;
    }
}
