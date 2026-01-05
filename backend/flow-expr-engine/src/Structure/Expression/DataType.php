<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Expression;

/**
 * Data type.
 */
enum DataType: string
{
    case String = 'string';
    case Number = 'number';
    case Array = 'array';
    case Object = 'object';
    case Boolean = 'boolean';
    case Null = 'null';
    case Expression = 'expression';

    public static function make(?string $input = null): ?self
    {
        // Categorize integer as number
        if ($input == 'integer') {
            $input = 'number';
        }
        return self::tryFrom(strtolower($input ?? ''));
    }

    public static function makeByValue(mixed $value): ?DataType
    {
        $valueType = strtolower(gettype($value));
        if ($valueType === 'array') {
            // If not a sequential array, consider it an object
            if (! empty($value) && array_keys($value) !== range(0, count($value) - 1)) {
                $valueType = 'object';
            }
        }
        if (is_string($value) && is_numeric($value)) {
            $valueType = 'number';
        }

        return DataType::make($valueType);
    }
}
