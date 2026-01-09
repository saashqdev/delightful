<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\LongTermMemory\Entity\ValueObject;

/**
 * 记忆status枚举.
 */
enum MemoryStatus: string
{
    case PENDING = 'pending';                   // 待accept(theonetimegenerate记忆o clock)
    case ACTIVE = 'active';                     // in effect(记忆alreadybeaccept,pending_contentfornull)
    case PENDING_REVISION = 'pending_revision'; // 待修订(记忆alreadybeaccept,butpending_contentnotfornull)

    /**
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => '待accept',
            self::ACTIVE => 'in effect',
            self::PENDING_REVISION => '待修订',
        };
    }

    /**
     * get所havestatusvalue.
     */
    public static function getAllValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * checkstatuswhethervalid.
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::getAllValues(), true);
    }
}
