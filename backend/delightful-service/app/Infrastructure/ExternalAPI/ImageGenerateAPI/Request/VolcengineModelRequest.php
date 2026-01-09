<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request;

class VolcengineModelRequest extends ImageGenerateRequest
{
    // inside置超minutefeature，startbackcanwillup述宽高均multiplyby2return，此parameteropenbackdelaywillhaveincrease
    // 如up述宽高均for512and512，此parametercloseoutgraph 512*512 ，此parameteropenoutgraph1024 * 1024
    private bool $useSr = false;

    // 目front只support url
    private array $referenceImages = [];

    public function __construct(string $width = '512', string $height = '512', string $prompt = '', string $negativePrompt = '')
    {
        parent::__construct($width, $height, $prompt, $negativePrompt);
    }

    public function getUseSr(): bool
    {
        return $this->useSr;
    }

    public function setUseSr(bool $useSr): void
    {
        $this->useSr = $useSr;
    }

    public function getReferenceImage(): array
    {
        return $this->referenceImages;
    }

    public function setReferenceImage(array $referenceImages): void
    {
        $this->referenceImages = $referenceImages;
    }

    public function getOrganizationCode(): ?string
    {
        return $this->organizationCode;
    }

    public function setOrganizationCode(?string $organizationCode): void
    {
        $this->organizationCode = $organizationCode;
    }
}
