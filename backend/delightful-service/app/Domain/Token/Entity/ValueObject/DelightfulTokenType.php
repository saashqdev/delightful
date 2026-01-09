<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Token\Entity\ValueObject;

/**
 * tokentype:0:账号,1:user,2:organization,3:应用,4:流程.
 */
enum DelightfulTokenType: int
{
    // user(organization下的oneuser),type_relation_value为userid
    case User = 0;

    // 账号,type_relation_value为账号id
    case Account = 1;

    // organization,type_relation_value为organizationid
    case Organization = 2;

    // 应用,type_relation_value为应用id
    case App = 3;

    // 流程,type_relation_value为流程id
    case Flow = 4;

    // 天书开放平台
    case TeamshareOpenPlatform = 5;

    /**
     * 通过枚举值名称的stringget枚举值.
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
