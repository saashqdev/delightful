<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Service;

use App\Domain\Contact\DTO\UserUpdateDTO;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Repository\Facade\DelightfulUserRepositoryInterface;
use App\Domain\Contact\Service\Facade\DelightfulUserDomainExtendInterface;
use App\Infrastructure\Core\Traits\DataIsolationTrait;

class DelightfulUserDomainExtendService implements DelightfulUserDomainExtendInterface
{
    use DataIsolationTrait;

    public function __construct(
        protected DelightfulUserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * whetherallowupdateuserinformation.
     * returnallowmodifyfield.
     */
    public function getUserUpdatePermission(DataIsolation $dataIsolation): array
    {
        $userId = $dataIsolation->getCurrentUserId();
        if (empty($userId)) {
            return [];
        }
        return ['avatar_url', 'nickname'];
    }

    /**
     * updateuserinformation.
     */
    public function updateUserInfo(DataIsolation $dataIsolation, UserUpdateDTO $userUpdateDTO): int
    {
        $permission = $this->getUserUpdatePermission($dataIsolation);

        $userId = $dataIsolation->getCurrentUserId();
        $updateFilter = [];

        // processavatarURL
        if (in_array('avatar_url', $permission) && $userUpdateDTO->getAvatarUrl() !== null) {
            $updateFilter['avatar_url'] = $userUpdateDTO->getAvatarUrl();
        }

        // processnickname
        if (in_array('nickname', $permission) && $userUpdateDTO->getNickname() !== null) {
            $updateFilter['nickname'] = $userUpdateDTO->getNickname();
        }

        return $this->userRepository->updateDataById($userId, $updateFilter);
    }
}
