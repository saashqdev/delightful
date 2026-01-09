<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * userid的associatevalue的含义.
 */
enum UserIdRelationType: int
{
    /**
     * organizationencoding
     */
    case organizationCode = 0;

    /**
     * applicationencoding
     */
    case applicationCode = 1;

    /**
     * application的createorganizationencoding
     */
    case applicationCreatedOrganizationCode = 2;

    /**
     * 将枚举typeconvert:
     */
    public static function getCaseFromUserIdType(UserIdType $userIdType): self
    {
        return match ($userIdType) {
            UserIdType::UserId => self::organizationCode,
            UserIdType::OpenId => self::applicationCode,
            UserIdType::UnionId => self::applicationCreatedOrganizationCode,
        };
    }
}
