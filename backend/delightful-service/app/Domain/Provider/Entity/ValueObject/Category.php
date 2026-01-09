<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

enum Category: string
{
    case LLM = 'llm';
    case VLM = 'vlm';

    public function label(): string
    {
        return match ($this) {
            self::LLM => '大model',
            self::VLM => '视觉model',
        };
    }
}
