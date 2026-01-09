<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Mode\DTO\ValueObject;

enum ModelStatus: string
{
    case Normal = 'normal';
    case Disabled = 'disabled';
    case Deleted = 'deleted';

    /**
     * getstatus描述.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::Normal => '正常',
            self::Disabled => '已禁用',
            self::Deleted => '已delete',
        };
    }

    /**
     * check是否为可用status
     */
    public function isAvailable(): bool
    {
        return $this === self::Normal;
    }
}
