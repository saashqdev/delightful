<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * userquerytype.
 */
enum UserQueryType: int
{
    // personmember
    case User = 1;

    // personmember + department
    case UserAndDepartment = 2;

    // personmember + department(completepath)
    case UserAndDepartmentFullPath = 3;

    /**
     * @return int[]
     */
    public static function types(): array
    {
        return [
            self::User->value,
            self::UserAndDepartment->value,
            self::UserAndDepartmentFullPath->value,
        ];
    }
}
