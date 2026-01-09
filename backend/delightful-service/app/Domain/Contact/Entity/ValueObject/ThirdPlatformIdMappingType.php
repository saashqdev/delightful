<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * 第third-party平台与麦吉的department、user、organizationencoding、null间encodingetc的mapping关系record.
 */
enum ThirdPlatformIdMappingType: string
{
    // department
    case Department = 'department';

    // user
    case User = 'user';

    // organization
    case Organization = 'organization';

    // null间
    case Space = 'space';
}
