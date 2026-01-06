<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Persistence;

use App\Domain\Chat\Entity\DelightfulFriendEntity;
use App\Domain\Chat\Repository\Facade\DelightfulFriendRepositoryInterface;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulFriendModel;
use App\Domain\Contact\DTO\FriendQueryDTO;

class DelightfulFriendRepository implements DelightfulFriendRepositoryInterface
{
    public function __construct(
        protected DelightfulFriendModel $friend
    ) {
    }

    public function insertFriend(array $friendEntity): void
    {
        $this->friend::query()->create($friendEntity);
    }

    public function isFriend(string $userId, string $friendId): bool
    {
        $friend = $this->friend::query()->where('user_id', $userId)->where('friend_id', $friendId)->first();
        return $friend !== null;
    }

    /**
     * @return DelightfulFriendEntity[]
     */
    public function getFriendList(FriendQueryDTO $friendQueryDTO, string $userId): array
    {
        $friendType = $friendQueryDTO->getFriendType()->value;
        $query = $this->friend::query()->where('user_id', $userId);
        if (in_array($friendType, [0, 1])) {
            $query->where('friend_type', $friendType);
        }
        if (! empty($friendQueryDTO->getUserIds())) {
            $query->whereIn('friend_id', $friendQueryDTO->getUserIds());
        }
        $friends = $query->get()->toArray();
        return array_map(function ($friend) {
            return new DelightfulFriendEntity($friend);
        }, $friends);
    }
}
