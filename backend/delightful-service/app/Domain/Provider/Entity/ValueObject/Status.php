<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

enum Status: int
{
    case Disabled = 0;
    case Enabled = 1;

    public function label(): string
    {
        return match ($this) {
            self::Disabled => 'disable',
            self::Enabled => 'enable',
        };
    }

    public function isEnabled(): bool
    {
        return $this === self::Enabled;
    }
}
