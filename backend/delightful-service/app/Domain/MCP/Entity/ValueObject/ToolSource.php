<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\MCP\Entity\ValueObject;

/**
 * toolcome源: 0:unknowncome源, 1:FlowTool.
 */
enum ToolSource: int
{
    // unknowncome源
    case Unknown = 0;

    // FlowTool
    case FlowTool = 1;

    /**
     * get标signature称.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Unknown => 'unknowncome源',
            self::FlowTool => 'FlowTool',
        };
    }

    /**
     * pass枚举valueget枚举object.
     */
    public static function fromValue(int $value): ?ToolSource
    {
        foreach (self::cases() as $source) {
            if ($source->value === $value) {
                return $source;
            }
        }
        return null;
    }
}
