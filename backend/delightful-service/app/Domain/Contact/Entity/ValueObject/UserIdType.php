<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * idtype:user_id/open_id/union_id.
 */
enum UserIdType: string
{
    /**
     * organizationinside唯one
     */
    case UserId = 'user_id';

    /**
     * organizationsomeapplicationdown唯one
     */
    case OpenId = 'open_id';

    /**
     * applicationcreateorganizationdown唯one(useatapplication跨organizationtraceuseat).
     */
    case UnionId = 'union_id';

    public function getPrefix(): string
    {
        return match ($this) {
            self::UserId => 'usi',
            self::OpenId => 'opi',
            self::UnionId => 'uni',
        };
    }

    public static function getCaseFromPrefix(string $prefix): ?self
    {
        return match ($prefix) {
            'usi' => self::UserId,
            'opi' => self::OpenId,
            'uni' => self::UnionId,
            default => null
        };
    }
}
