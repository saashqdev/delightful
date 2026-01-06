<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Facade;

use App\Domain\Chat\Entity\DelightfulFriendEntity;
use App\Domain\Contact\DTO\FriendQueryDTO;

interface DelightfulFriendRepositoryInterface
{
    public function insertFriend(array $friendEntity): void;

    public function isFriend(string $userId, string $friendId): bool;

    /**
     * @return DelightfulFriendEntity[]
     */
    public function getFriendList(FriendQueryDTO $friendQueryDTO, string $userId): array;
}
