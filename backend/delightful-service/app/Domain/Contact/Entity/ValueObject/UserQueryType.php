<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * userquerytype。
 */
enum UserQueryType: int
{
    // 人员
    case User = 1;

    // 人员 + department
    case UserAndDepartment = 2;

    // 人员 + department（完整路径）
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
