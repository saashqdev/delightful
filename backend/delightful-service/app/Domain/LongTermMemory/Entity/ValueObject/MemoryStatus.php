<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\LongTermMemory\Entity\ValueObject;

/**
 * memorystatusenum.
 */
enum MemoryStatus: string
{
    case PENDING = 'pending';                   // pendingaccept(theonetimegeneratememoryo clock)
    case ACTIVE = 'active';                     // in effect(memoryalreadybeaccept,pending_contentfornull)
    case PENDING_REVISION = 'pending_revision'; // pendingrevision(memoryalreadybeaccept,butpending_contentnotfornull)

    /**
     * getstatusdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'pendingaccept',
            self::ACTIVE => 'in effect',
            self::PENDING_REVISION => 'pendingrevision',
        };
    }

    /**
     * get havestatusvalue.
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
