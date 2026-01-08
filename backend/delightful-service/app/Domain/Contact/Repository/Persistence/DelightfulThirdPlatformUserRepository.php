<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Persistence;

use App\Domain\Contact\Repository\Facade\DelightfulThirdPlatformUserRepositoryInterface;
use App\Domain\Contact\Repository\Persistence\Model\ThirdPlatformUserModel;

readonly class DelightfulThirdPlatformUserRepository implements DelightfulThirdPlatformUserRepositoryInterface
{
    public function __construct(
        protected ThirdPlatformUserModel $userModel,
    ) {
    }
}
