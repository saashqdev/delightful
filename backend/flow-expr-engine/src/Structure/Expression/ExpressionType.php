<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Expression;

enum ExpressionType: string
{
    case Field = 'fields';
    case Input = 'input';
    case Method = 'methods';

    // Special type for const, not involved in calculations, only for storage
    case Member = 'member';
    case Datetime = 'datetime';
    case Multiple = 'multiple';
    case Select = 'select';
    case Checkbox = 'checkbox';
    case DepartmentNames = 'department_names';
    case Names = 'names';

    public static function make(?string $input): ?ExpressionType
    {
        if (is_null($input)) {
            return null;
        }
        // Try direct conversion first
        $type = self::tryFrom($input);
        if ($type) {
            return $type;
        }
        // May have interfering data, like fields_123
        $type = explode('_', $input)[0];
        return ExpressionType::tryFrom($type);
    }

    public function isDisplayValue(): bool
    {
        return in_array($this, [
            self::Member,
            self::Datetime,
            self::Multiple,
            self::Select,
            self::Checkbox,
            self::DepartmentNames,
            self::Names,
        ]);
    }
}
