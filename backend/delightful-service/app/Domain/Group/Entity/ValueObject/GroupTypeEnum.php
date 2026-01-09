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

    // inside部培训群
    case InternalTraining = 2;

    // inside部willdiscussion group
    case InternalMeeting = 3;

    // inside部project群
    case InternalProject = 4;

    // inside部工single群
    case InternalWorkOrder = 5;

    // outsidedepartment group
    case External = 6;
}
