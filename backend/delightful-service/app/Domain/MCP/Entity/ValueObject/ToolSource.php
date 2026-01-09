<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\MCP\Entity\ValueObject;

/**
 * tool来源: 0:未知来源, 1:FlowTool.
 */
enum ToolSource: int
{
    // 未知来源
    case Unknown = 0;

    // FlowTool
    case FlowTool = 1;

    /**
     * get标签名称.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Unknown => '未知来源',
            self::FlowTool => 'FlowTool',
        };
    }

    /**
     * 通过枚举值get枚举对象.
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
