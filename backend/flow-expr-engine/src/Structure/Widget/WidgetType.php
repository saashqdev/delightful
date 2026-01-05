<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Widget;

enum WidgetType: string
{
    /**
     * Root node.
     */
    case Root = 'root';

    /**
     * Single-line input box.
     */
    case Input = 'input';

    /**
     * Expression input box.
     */
    case Expression = 'expression';

    /**
     * Password input box.
     */
    case Password = 'input-password';

    /**
     * Dropdown selector.
     */
    case Select = 'select';

    /**
     * Number input box.
     */
    case Number = 'input-number';

    /**
     * Switch.
     */
    case Switch = 'switch';

    /**
     * Array - value is in its own value.
     */
    case Array = 'array';

    /**
     * Object - value is in the containerFields of the subset.
     */
    case Object = 'object';

    /**
     * Linkage.
     */
    case Linkage = 'linkage';

    /**
     * Text area.
     */
    case Textarea = 'textarea';

    /**
     * Member.
     */
    case Member = 'member';

    /**
     * Date picker.
     */
    case TimePicker = 'time-picker';

    /**
     * Files.
     */
    case Files = 'files';

    /**
     * Checkbox.
     */
    case Checkbox = 'checkbox';

    public static function make(null|int|string $input = null): ?self
    {
        // Compatible with the old version
        if ($input === 'password') {
            return self::Password;
        }
        if ($input === 'number') {
            return self::Number;
        }
        return self::tryFrom($input ?? '');
    }

    public function isDesensitization(): bool
    {
        return $this === self::Password;
    }
}
