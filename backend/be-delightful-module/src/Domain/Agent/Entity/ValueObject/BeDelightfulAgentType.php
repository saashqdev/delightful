<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

enum BeDelightfulAgentType: int
{
    /**
     * Built-in agent.
     */
    case Built_In = 1;

    /**
     * Custom agent.
     */
    case Custom = 2;

    /**
     * Get type description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::Built_In => 'Built-in',
            self::Custom => 'Custom',
        };
    }

    /**
     * Whether it is a built-in type.
     */
    public function isBuiltIn(): bool
    {
        return $this === self::Built_In;
    }

    /**
     * Whether it is a custom type.
     */
    public function isCustom(): bool
    {
        return $this === self::Custom;
    }

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
}
