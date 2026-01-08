<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Repository\Facade;

use App\Domain\Agent\Entity\DelightfulBotThirdPlatformChatEntity;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulBotThirdPlatformChatQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulBotThirdPlatformChatRepositoryInterface
{
    public function save(DelightfulBotThirdPlatformChatEntity $entity): DelightfulBotThirdPlatformChatEntity;

    public function getByKey(string $key): ?DelightfulBotThirdPlatformChatEntity;

    public function getById(int $id): ?DelightfulBotThirdPlatformChatEntity;

    /**
     * @return array{total: int, list: DelightfulBotThirdPlatformChatEntity[]}
     */
    public function queries(DelightfulBotThirdPlatformChatQuery $query, Page $page): array;

    public function destroy(DelightfulBotThirdPlatformChatEntity $entity): void;
}
