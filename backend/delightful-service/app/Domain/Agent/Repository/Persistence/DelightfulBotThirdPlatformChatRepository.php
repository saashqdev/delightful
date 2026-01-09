<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Repository\Persistence;

use App\Domain\Agent\Entity\DelightfulBotThirdPlatformChatEntity;
use App\Domain\Agent\Entity\ValueObject\Query\DelightfulBotThirdPlatformChatQuery;
use App\Domain\Agent\Factory\DelightfulAgentThirdPlatformChatFactory;
use App\Domain\Agent\Repository\Facade\DelightfulBotThirdPlatformChatRepositoryInterface;
use App\Domain\Agent\Repository\Persistence\Model\DelightfulBotThirdPlatformChatModel;
use App\Infrastructure\Core\AbstractRepository;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulBotThirdPlatformChatRepository extends AbstractRepository implements DelightfulBotThirdPlatformChatRepositoryInterface
{
    public function save(DelightfulBotThirdPlatformChatEntity $entity): DelightfulBotThirdPlatformChatEntity
    {
        if (! empty($entity->getId())) {
            /** @var DelightfulBotThirdPlatformChatModel $model */
            $model = DelightfulBotThirdPlatformChatModel::query()->find($entity->getId());
            $saveData = [
                // 只allow修改whetherenable
                'identification' => $entity->getIdentification(),
                'enabled' => $entity->isEnabled(),
            ];
            if ($entity->isAllUpdate()) {
                $saveData = $entity->toArray();
            }
        } else {
            $model = new DelightfulBotThirdPlatformChatModel();
            $saveData = $entity->toArray();
        }
        $model->fill($saveData);
        $model->save();
        return DelightfulAgentThirdPlatformChatFactory::modelToEntity($model);
    }

    public function getByKey(string $key): ?DelightfulBotThirdPlatformChatEntity
    {
        $model = DelightfulBotThirdPlatformChatModel::query()->where('key', $key)->first();
        if (empty($model)) {
            return null;
        }
        return DelightfulAgentThirdPlatformChatFactory::modelToEntity($model);
    }

    public function getById(int $id): ?DelightfulBotThirdPlatformChatEntity
    {
        /** @var null|DelightfulBotThirdPlatformChatModel $model */
        $model = DelightfulBotThirdPlatformChatModel::query()->find($id);
        if (empty($model)) {
            return null;
        }
        return DelightfulAgentThirdPlatformChatFactory::modelToEntity($model);
    }

    public function queries(DelightfulBotThirdPlatformChatQuery $query, Page $page): array
    {
        $queryBuilder = DelightfulBotThirdPlatformChatModel::query();
        if (! empty($query->getBotId())) {
            $queryBuilder->where('bot_id', $query->getBotId());
        }
        $data = $this->getByPage($queryBuilder, $page, $query);
        $list = [];
        foreach ($data['list'] ?? [] as $datum) {
            $entity = DelightfulAgentThirdPlatformChatFactory::modelToEntity($datum);
            if ($query->getKeyBy() === 'key') {
                $list[$entity->getKey()] = $entity;
            } else {
                $list[] = $entity;
            }
        }
        $data['list'] = $list;
        return $data;
    }

    public function destroy(DelightfulBotThirdPlatformChatEntity $entity): void
    {
        DelightfulBotThirdPlatformChatModel::query()->where('id', $entity->getId())->delete();
    }
}
