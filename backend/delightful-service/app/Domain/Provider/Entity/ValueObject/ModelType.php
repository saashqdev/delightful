<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

enum ModelType: int
{
    case TEXT_TO_IMAGE = 0; // text generationgraph
    case IMAGE_TO_IMAGE = 1; // graph生graph
    case IMAGE_ENHANCE = 2; // imageenhance
    case LLM = 3; // bigmodel
    case EMBEDDING = 4; // embedding

    public function label(): string
    {
        return match ($this) {
            self::TEXT_TO_IMAGE => 'text generationgraph',
            self::IMAGE_TO_IMAGE => 'graph生graph',
            self::IMAGE_ENHANCE => 'imageenhance',
            self::LLM => 'bigmodel',
            self::EMBEDDING => 'embedding',
        };
    }

    public function isLLM(): bool
    {
        return $this === self::LLM;
    }

    public function isEmbedding(): bool
    {
        return $this === self::EMBEDDING;
    }

    public function isVLM(): bool
    {
        return $this === self::TEXT_TO_IMAGE || $this === self::IMAGE_TO_IMAGE;
    }
}
