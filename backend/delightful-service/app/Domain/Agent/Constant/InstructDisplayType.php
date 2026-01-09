<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

use function Hyperf\Translation\__;

class InstructDisplayType
{
    public const NORMAL = 1;    // 普通

    public const SYSTEM = 2;    // system

    /**
     * verify显示类型是否valid.
     */
    public static function isValid(int $type): bool
    {
        return in_array($type, [
            self::NORMAL,
            self::SYSTEM,
        ], true);
    }

    /**
     * get所有显示类型及其国际化tag.
     * @return array<int, string>
     */
    public static function getTypeOptions(): array
    {
        return [
            self::NORMAL => __('agent.instruct_display_type_normal'),
            self::SYSTEM => __('agent.instruct_display_type_system'),
        ];
    }
}
