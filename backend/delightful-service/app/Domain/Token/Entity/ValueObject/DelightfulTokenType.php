<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Token\Entity\ValueObject;

/**
 * tokentype:0:账number,1:user,2:organization,3:application,4:process.
 */
enum DelightfulTokenType: int
{
    // user(organizationdown的oneuser),type_relation_value为userid
    case User = 0;

    // 账number,type_relation_value为账numberid
    case Account = 1;

    // organization,type_relation_value为organizationid
    case Organization = 2;

    // application,type_relation_value为applicationid
    case App = 3;

    // process,type_relation_value为processid
    case Flow = 4;

    // day书开放平台
    case TeamshareOpenPlatform = 5;

    /**
     * pass枚举valuename的stringget枚举value.
     */
    public static function getCaseFromName(string $typeName): ?DelightfulTokenType
    {
        foreach (self::cases() as $userType) {
            if ($userType->name === $typeName) {
                return $userType;
            }
        }
        return null;
    }
}
