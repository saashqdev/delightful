<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Persistence;

use App\Domain\Contact\Repository\Facade\DelightfulThirdPlatformDepartmentUserRepositoryInterface;
use App\Domain\Contact\Repository\Persistence\Model\ThirdPlatformDepartmentUserModel;

class DelightfulThirdPlatformDepartmentUserRepository implements DelightfulThirdPlatformDepartmentUserRepositoryInterface
{
    public function __construct(
        protected ThirdPlatformDepartmentUserModel $departmentUserModel,
    ) {
    }
}
