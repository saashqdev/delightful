<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * delightful_usertype.
 */
enum UserType: int
{
    // ai
    case Ai = 0;

    // personcategory
    case Human = 1;

    /**
     * willenumtypeconvert:0转forai,1转for user.
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
