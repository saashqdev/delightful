<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

enum ProviderOriginalModelType: int
{
    case System = 0;
    case Custom = 1;

    public function label(): string
    {
        return match ($this) {
            self::System => '系统默认',
            self::Custom => '自己添加',
        };
    }

    public function isSystem(): bool
    {
        return $this === self::System;
    }

    public function isCustom(): bool
    {
        return $this === self::Custom;
    }
}
