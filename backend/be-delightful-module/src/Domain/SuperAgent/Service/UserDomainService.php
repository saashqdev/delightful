<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Service;

use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use BeDelightful\BeDelightful\Domain\BeAgent\Entity\UserAuthorization;

class UserDomainService
{
    public function __construct(
        protected DelightfulUserDomainService $delightfulUserDomainService,
    ) {
    }

    public function getUserEntity(string $userId): ?DelightfulUserEntity
    {
        return $this->delightfulUserDomainService->getUserById($userId);
    }

    public function getUserAuthorization(string $userId): ?UserAuthorization
    {
        $delightfulUserEntity = $this->getUserEntity($userId);

        return UserAuthorization::fromUserEntity($delightfulUserEntity);
    }
}
