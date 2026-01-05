<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Condition;

enum CompareType: string
{
    /**
     * Equals.
     */
    case Equals = 'equals';

    /**
     * Not equals.
     */
    case NoEquals = 'no_equals';

    /**
     * Contains.
     */
    case Contains = 'contains';

    /**
     * Does not contain.
     */
    case NoContains = 'no_contains';

    /**
     * Greater than.
     */
    case Gt = 'gt';

    /**
     * Less than.
     */
    case Lt = 'lt';

    /**
     * Greater than or equal to.
     */
    case Gte = 'gte';

    /**
     * Less than or equal to.
     */
    case Lte = 'lte';

    /**
     * No value
     */
    case Empty = 'empty';

    /**
     * Has value
     */
    case NotEmpty = 'not_empty';

    /**
     * Is empty.
     */
    case Valuable = 'valuable';

    /**
     * Not empty.
     */
    case NoValuable = 'no_valuable';

    public static function make(?string $input): ?CompareType
    {
        $compareType = CompareType::tryFrom($input ?? '');
        if (! is_null($compareType)) {
            return $compareType;
        }
        // Special types
        return match ($input) {
            '>' => CompareType::Gt,
            '<' => CompareType::Lt,
            '>=' => CompareType::Gte,
            '<=' => CompareType::Lte,
            '!=' => CompareType::NoEquals,
            '=' ,'==', '===' => CompareType::Equals,
            default => null,
        };
    }

    public function isRightOperandsRequired(): bool
    {
        return in_array($this, [
            CompareType::Equals,
            CompareType::NoEquals,
            CompareType::Contains,
            CompareType::NoContains,
            CompareType::Gt,
            CompareType::Lt,
            CompareType::Gte,
            CompareType::Lte,
        ]);
    }
}
