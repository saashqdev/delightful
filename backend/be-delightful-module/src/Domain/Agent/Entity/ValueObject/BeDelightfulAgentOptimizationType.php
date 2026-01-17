<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

use Exception;

enum BeDelightfulAgentOptimizationType: string
{
    case None = 'none';
    case OptimizeNameDescription = 'optimize_name_description';
    case OptimizeContent = 'optimize_content';
    case OptimizeName = 'optimize_name';
    case OptimizeDescription = 'optimize_description';

    /**
     * Get enum description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::OptimizeNameDescription => 'Optimize name and description',
            self::OptimizeContent => 'Optimize content',
            self::OptimizeName => 'Optimize name',
            self::OptimizeDescription => 'Optimize description',
            self::None => throw new Exception('To be implemented'),
        };
    }

    /**
     * Get all enum values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Create enum instance from string.
     */
    public static function fromString(string $value): self
    {
        $type = self::tryFrom($value);
        if ($type === null) {
            return self::None;
        }
        return $type;
    }

    public function isNone(): bool
    {
        return $this === self::None;
    }
}
