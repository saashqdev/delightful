<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * department成员求和type。
 */
enum DepartmentSumType: int
{
    // 1：returndepartment直属usertotal，
    case DirectEmployee = 1;

    // 2：return本department + 所有子departmentusertotal
    case All = 2;
}
