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
     * organization内唯一
     */
    case UserId = 'user_id';

    /**
     * organization的某个应用下唯一
     */
    case OpenId = 'open_id';

    /**
     * 应用的createorganization下唯一(用于应用跨organization追踪用于).
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
