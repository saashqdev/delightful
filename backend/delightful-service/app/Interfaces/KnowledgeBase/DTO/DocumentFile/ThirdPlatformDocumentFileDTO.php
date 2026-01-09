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

    // thethird-partyfiletype,customizefield,bythethird-partyplatformsetting
    public ?string $thirdFileType = null;

    // thethird-partyfileextension名,customizefield,bythethird-partyplatformsetting
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
