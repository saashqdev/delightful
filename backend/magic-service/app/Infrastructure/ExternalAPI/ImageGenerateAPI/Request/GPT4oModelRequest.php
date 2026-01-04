<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request;

class GPT4oModelRequest extends ImageGenerateRequest
{
    protected array $referImages = [];

    public function setReferImages(array $referImages): self
    {
        $this->referImages = $referImages;
        return $this;
    }

    public function getReferImages(): array
    {
        return $this->referImages;
    }
}
