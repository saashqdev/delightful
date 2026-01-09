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
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::Normal => 'normal',
            self::Disabled => 'disabled',
            self::Deleted => 'alreadydelete',
        };
    }

    /**
     * checkwhetherforcanusestatus
     */
    public function isAvailable(): bool
    {
        return $this === self::Normal;
    }
}
