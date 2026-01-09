<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Group\Entity\ValueObject;

enum GroupTypeEnum: int
{
    // insidedepartment group
    case Internal = 1;

    // insidedepartment training group
    case InternalTraining = 2;

    // insidedepartmentwilldiscussion group
    case InternalMeeting = 3;

    // insidedepartmentprojectgroup
    case InternalProject = 4;

    // insidedepartmentworkersinglegroup
    case InternalWorkOrder = 5;

    // outsidedepartment group
    case External = 6;
}
