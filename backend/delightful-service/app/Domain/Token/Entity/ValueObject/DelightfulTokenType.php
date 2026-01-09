<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Token\Entity\ValueObject;

/**
 * tokentype:0:accountnumber,1:user,2:organization,3:application,4:process.
 */
enum DelightfulTokenType: int
{
    // user(organizationdownoneuser),type_relation_valueforuserid
    case User = 0;

    // accountnumber,type_relation_valueforaccountnumberid
    case Account = 1;

    // organization,type_relation_valuefororganizationid
    case Organization = 2;

    // application,type_relation_valueforapplicationid
    case App = 3;

    // process,type_relation_valueforprocessid
    case Flow = 4;

    // daybookopenputplatform
    case TeamshareOpenPlatform = 5;

    /**
     * passenumvaluenamestringgetenumvalue.
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
