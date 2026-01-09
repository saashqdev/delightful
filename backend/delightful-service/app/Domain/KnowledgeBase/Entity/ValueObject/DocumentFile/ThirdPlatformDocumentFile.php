<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject\DocumentFile;

use App\Domain\KnowledgeBase\Entity\ValueObject\DocType;
use App\Domain\KnowledgeBase\Entity\ValueObject\DocumentFile\Interfaces\ThirdPlatformDocumentFileInterface;

class ThirdPlatformDocumentFile extends AbstractDocumentFile implements ThirdPlatformDocumentFileInterface
{
    public string $thirdFileId;

    public string $platformType;

    // 第third-partyfiletype，customize字段，由第third-party平台setting
    public ?string $thirdFileType = null;

    // 第third-partyfileextension名，customize字段，由第third-party平台setting
    public ?string $thirdFileExtensionName = null;

    public function getThirdFileId(): string
    {
        return $this->thirdFileId;
    }

    public function setThirdFileId(string $thirdFileId): static
    {
        $this->thirdFileId = $thirdFileId;
        return $this;
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

    public function getDocType(): int
    {
        return $this->docType ?? DocType::TXT->value;
    }

    public function getThirdFileType(): ?string
    {
        return $this->thirdFileType;
    }

    public function setThirdFileType(?string $thirdFileType): static
    {
        $this->thirdFileType = $thirdFileType;
        return $this;
    }

    public function getThirdFileExtensionName(): ?string
    {
        return $this->thirdFileExtensionName;
    }

    public function setThirdFileExtensionName(?string $thirdFileExtensionName): static
    {
        $this->thirdFileExtensionName = $thirdFileExtensionName;
        return $this;
    }

    protected function initType(): DocumentFileType
    {
        return DocumentFileType::THIRD_PLATFORM;
    }
}
