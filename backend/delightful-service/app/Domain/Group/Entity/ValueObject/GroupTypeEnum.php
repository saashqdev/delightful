<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Group\Entity\ValueObject;

enum GroupTypeEnum: int
{
    // inside部群
    case Internal = 1;

    // inside部培训群
    case InternalTraining = 2;

    // inside部will议群
    case InternalMeeting = 3;

    // inside部project群
    case InternalProject = 4;

    // inside部工单群
    case InternalWorkOrder = 5;

    // outside部群
    case External = 6;
}
