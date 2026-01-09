<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\MCP\Entity\ValueObject;

/**
 * toolcomesource: 0:unknowncomesource, 1:FlowTool.
 */
enum ToolSource: int
{
    // unknowncomesource
    case Unknown = 0;

    // FlowTool
    case FlowTool = 1;

    /**
     * getmarksignature称.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::Unknown => 'unknowncomesource',
            self::FlowTool => 'FlowTool',
        };
    }

    /**
     * passenumvaluegetenumobject.
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
