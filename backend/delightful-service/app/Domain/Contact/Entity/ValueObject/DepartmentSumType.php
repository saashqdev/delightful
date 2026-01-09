<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * departmentmemberrequestandtype.
 */
enum DepartmentSumType: int
{
    // 1:returndepartmentdirectly underusertotal,
    case DirectEmployee = 1;

    // 2:returnthisdepartment +  havechilddepartmentusertotal
    case All = 2;
}
