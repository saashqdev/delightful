<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * employeetype.
 */
enum EmployeeType: int
{
    // unknown(such asisperson版user)
    case Unknown = 0;

    // justtypeemployee
    case Formal = 1;

    // 实习生
    case Intern = 2;

    // outsidepackage
    case Outsourcing = 3;

    // 劳务派遣
    case LaborDispatch = 4;

    // consultant
    case Consultant = 5;
}
