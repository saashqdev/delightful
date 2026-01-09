<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\KnowledgeBase\DTO\Request;

use App\Infrastructure\Core\AbstractRequestDTO;

class CreateFragmentRequestDTO extends AbstractRequestDTO
{
    public string $knowledgeBaseCode;

    public string $documentCode;

    public string $content;

    public array $metadata = [];

    public static function getHyperfValidationRules(): array
    {
        return [
            'knowledge_base_code' => 'required|string|max:255',
            'document_code' => 'required|string|max:255',
            'content' => 'required|string|max:65535',
            'metadata' => 'array',
        ];
    }

    public static function getHyperfValidationMessage(): array
    {
        return [
            'knowledge_base_code.required' => 'knowledge baseencodingcannot为null',
            'knowledge_base_code.max' => 'knowledge baseencodinglengthcannot超过255字符',
            'document_code.required' => 'documentencodingcannot为null',
            'document_code.max' => 'documentencodinglengthcannot超过255字符',
            'content.required' => 'slicesegmentcontentcannot为null',
            'content.max' => 'slicesegmentcontentlengthcannot超过65535字符',
        ];
    }

    public function getKnowledgeBaseCode(): string
    {
        return $this->knowledgeBaseCode;
    }

    public function setKnowledgeBaseCode(string $knowledgeBaseCode): CreateFragmentRequestDTO
    {
        $this->knowledgeBaseCode = $knowledgeBaseCode;
        return $this;
    }

    public function getDocumentCode(): string
    {
        return $this->documentCode;
    }

    public function setDocumentCode(string $documentCode): CreateFragmentRequestDTO
    {
        $this->documentCode = $documentCode;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): CreateFragmentRequestDTO
    {
        $this->content = $content;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): CreateFragmentRequestDTO
    {
        $this->metadata = $metadata;
        return $this;
    }
}
