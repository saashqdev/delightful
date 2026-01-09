<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\KnowledgeBase\DTO\DocumentFile;

use App\Domain\KnowledgeBase\Entity\ValueObject\DocumentFile\DocumentFileType;

class ThirdPlatformDocumentFileDTO extends AbstractDocumentFileDTO
{
    public string $platformType;

    public string $thirdFileId;

    // 第third-party文件type，自定义字段，由第third-party平台setting
    public ?string $thirdFileType = null;

    // 第third-party文件扩展名，自定义字段，由第third-party平台setting
    public ?string $thirdFileExtensionName = null;

    public function getThirdFileId(): string
    {
        return $this->thirdFileId;
    }

    public function setThirdFileId(string $thirdFileId): void
    {
        $this->thirdFileId = $thirdFileId;
    }

    public function getPlatformType(): string
    {
        return $this->platformType;
    }

    public function setPlatformType(string $platformType): static
    {
        $this->platformType = $platformType;
        return $this;
    }

    protected function initType(): DocumentFileType
    {
        return DocumentFileType::THIRD_PLATFORM;
    }
}
