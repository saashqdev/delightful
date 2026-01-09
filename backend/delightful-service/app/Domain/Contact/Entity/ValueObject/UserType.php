<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * delightful_user的type.
 */
enum UserType: int
{
    // ai
    case Ai = 0;

    // personcategory
    case Human = 1;

    /**
     * 将枚举typeconvert:0转为ai,1转为 user.
     */
    public static function getCaseFromName(string $typeName): ?self
    {
        foreach (self::cases() as $userType) {
            if ($userType->name === $typeName) {
                return $userType;
            }
        }
        return null;
    }
}
