<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Persistence;

use App\Domain\Chat\Entity\DelightfulMessageVersionEntity;
use App\Domain\Chat\Repository\Facade\DelightfulChatMessageVersionsRepositoryInterface;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulMessageVersionsModel;
use App\Infrastructure\Util\IdGenerator\IdGenerator;

class DelightfulMessageVersionsRepository implements DelightfulChatMessageVersionsRepositoryInterface
{
    public function __construct(
        protected DelightfulMessageVersionsModel $messageVersionsModel
    ) {
    }

    public function createMessageVersion(DelightfulMessageVersionEntity $messageVersionDTO): DelightfulMessageVersionEntity
    {
        $data = $messageVersionDTO->toArray();
        $time = date('Y-m-d H:i:s');
        $data['created_at'] = $time;
        $data['updated_at'] = $time;
        $data['deleted_at'] = null;
        $data['version_id'] = (string) IdGenerator::getSnowId();
        $this->messageVersionsModel::query()->create($data);
        return $this->assembleMessageVersionEntity($data);
    }

    /**
     * @return DelightfulMessageVersionEntity[]
     */
    public function getMessageVersions(string $delightfulMessageId): array
    {
        $data = $this->messageVersionsModel::query()
            ->where('delightful_message_id', $delightfulMessageId)
            ->get()
            ->toArray();
        $entities = [];
        foreach ($data as $item) {
            $entities[] = $this->assembleMessageVersionEntity($item);
        }
        return $entities;
    }

    // groupinstall DelightfulMessageVersionEntity object
    private function assembleMessageVersionEntity(array $data): DelightfulMessageVersionEntity
    {
        return new DelightfulMessageVersionEntity($data);
    }
}
