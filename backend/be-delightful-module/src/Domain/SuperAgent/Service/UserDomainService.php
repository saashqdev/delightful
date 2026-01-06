<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperDelightful\Domain\SuperAgent\Service;

use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use Delightful\SuperDelightful\Domain\SuperAgent\Entity\UserAuthorization;

class UserDomainService
{
    public function __construct(
        protected DelightfulUserDomainService $magicUserDomainService,
    ) {
    }

    public function getUserEntity(string $userId): ?DelightfulUserEntity
    {
        return $this->magicUserDomainService->getUserById($userId);
    }

    public function getUserAuthorization(string $userId): ?UserAuthorization
    {
        $magicUserEntity = $this->getUserEntity($userId);

        return UserAuthorization::fromUserEntity($magicUserEntity);
    }
}
