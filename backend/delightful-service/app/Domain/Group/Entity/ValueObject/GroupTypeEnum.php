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

    // inside部willdiscussion group
    case InternalMeeting = 3;

    // inside部projectgroup
    case InternalProject = 4;

    // insidedepartment工singlegroup
    case InternalWorkOrder = 5;

    // outsidedepartment group
    case External = 6;
}
