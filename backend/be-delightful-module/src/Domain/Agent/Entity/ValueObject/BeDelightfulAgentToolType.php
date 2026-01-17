<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

enum BeDelightfulAgentToolType: int
{
    // 1: Built-in
    case BuiltIn = 1;

    // 2: Official
    case Official = 2;

    // 3: Custom
    case Custom = 3;

    /**
     * Get all available enum values.
     * @return array<int>
     */
    public static function getAvailableValues(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    /**
     * Get all available enum value strings (for validation rules).
     */
    public static function getValidationRule(): string
    {
        return implode(',', self::getAvailableValues());
    }

    public function isRemote(): bool
    {
        return in_array($this, [self::Official, self::Custom], true);
    }

    public function isBuiltIn(): bool
    {
        return $this === self::BuiltIn;
    }
}
