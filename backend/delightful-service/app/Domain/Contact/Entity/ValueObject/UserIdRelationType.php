<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * userid的关联值的含义.
 */
enum UserIdRelationType: int
{
    /**
     * organization编码
     */
    case organizationCode = 0;

    /**
     * 应用编码
     */
    case applicationCode = 1;

    /**
     * 应用的createorganization编码
     */
    case applicationCreatedOrganizationCode = 2;

    /**
     * 将枚举type转换:
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
