<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Entity\ValueObject;

/**
 * thethird-partyplatformandMagicdepartment,user,organizationencoding,nullbetweenencodingetcmappingclosesystemrecord.
 */
enum ThirdPlatformIdMappingType: string
{
    // department
    case Department = 'department';

    // user
    case User = 'user';

    // organization
    case Organization = 'organization';

    // nullbetween
    case Space = 'space';
}
