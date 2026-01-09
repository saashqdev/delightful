<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * 员工type.
 */
enum EmployeeType: int
{
    // 未知(such as是个人版user)
    case Unknown = 0;

    // 正式员工
    case Formal = 1;

    // 实习生
    case Intern = 2;

    // 外package
    case Outsourcing = 3;

    // 劳务派遣
    case LaborDispatch = 4;

    // 顾问
    case Consultant = 5;
}
