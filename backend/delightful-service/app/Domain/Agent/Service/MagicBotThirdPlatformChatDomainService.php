<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Service;

use App\Domain\Agent\Entity\DelightfulBotThirdPlatformChatEntity;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulBotThirdPlatformChatQuery;
use App\Domain\Agent\Repository\Facade\DelightfulBotThirdPlatformChatRepositoryInterface;
use App\Infrastructure\Core\ValueObject\Page;
use Hyperf\DbConnection\Annotation\Transactional;

readonly class DelightfulBotThirdPlatformChatDomainService
{
    public function __construct(
        private DelightfulBotThirdPlatformChatRepositoryInterface $magicBotThirdPlatformChatRepository
    ) {
    }

    public function getByKey(string $key): ?DelightfulBotThirdPlatformChatEntity
    {
        return $this->magicBotThirdPlatformChatRepository->getByKey($key);
    }

    public function getById(int $id): ?DelightfulBotThirdPlatformChatEntity
    {
        return $this->magicBotThirdPlatformChatRepository->getById($id);
    }

    public function save(DelightfulBotThirdPlatformChatEntity $entity): DelightfulBotThirdPlatformChatEntity
    {
        $entity->prepareForSaving();
        return $this->magicBotThirdPlatformChatRepository->save($entity);
    }

    /**
     * @return array{total: int, list: DelightfulBotThirdPlatformChatEntity[]}
     */
    public function queries(DelightfulBotThirdPlatformChatQuery $query, Page $page): array
    {
        return $this->magicBotThirdPlatformChatRepository->queries($query, $page);
    }

    public function destroy(DelightfulBotThirdPlatformChatEntity $entity): void
    {
        $this->magicBotThirdPlatformChatRepository->destroy($entity);
    }

    /**
     * @param null|DelightfulBotThirdPlatformChatEntity[] $thirdPlatformList
     */
    #[Transactional]
    public function syncBotThirdPlatformList(string $botId, ?array $thirdPlatformList = null): void
    {
        if (is_null($thirdPlatformList)) {
            return;
        }

        $query = new DelightfulBotThirdPlatformChatQuery();
        $query->setBotId($botId);
        $query->setKeyBy('key');
        $historyList = $this->magicBotThirdPlatformChatRepository->queries($query, Page::createNoPage())['list'];

        foreach ($thirdPlatformList as $thirdPlatformChatEntity) {
            $thirdPlatformChatEntity->setBotId($botId);
            if ($historyThirdPlatformChatEntity = $historyList[$thirdPlatformChatEntity->getKey()] ?? null) {
                $thirdPlatformChatEntity->setId($historyThirdPlatformChatEntity->getId());
            } else {
                $thirdPlatformChatEntity->setId(null);
            }
            $this->save($thirdPlatformChatEntity);
            unset($historyList[$thirdPlatformChatEntity->getKey()]);
        }

        // 剩下的都是要删除的
        foreach ($historyList as $item) {
            $this->destroy($item);
        }
    }
}
