<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Persistence;

use App\Domain\Contact\Entity\DelightfulUserIdRelationEntity;
use App\Domain\Contact\Repository\Facade\DelightfulUserIdRelationRepositoryInterface;
use App\Domain\Contact\Repository\Persistence\Model\UserIdRelationModel;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Hyperf\DbConnection\Db;

readonly class DelightfulUserIdRelationRepository implements DelightfulUserIdRelationRepositoryInterface
{
    public function __construct(
        protected UserIdRelationModel $userIdRelationModel,
    ) {
    }

    public function createUserIdRelation(DelightfulUserIdRelationEntity $userIdRelationEntity): void
    {
        // generateassociateclose系
        $time = date('Y-m-d H:i:s');
        $id = IdGenerator::getSnowId();
        $userIdRelationEntity->setId($id);
        $this->userIdRelationModel::query()->create([
            'id' => $id,
            'delightful_id' => $userIdRelationEntity->getAccountId(),
            'id_type' => $userIdRelationEntity->getIdType()->value,
            'id_value' => $userIdRelationEntity->getIdValue(),
            'relation_type' => $userIdRelationEntity->getRelationType(),
            'relation_value' => $userIdRelationEntity->getRelationValue(),
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    public function getRelationIdExists(DelightfulUserIdRelationEntity $userIdRelationEntity): array
    {
        // according to account_id/id_type/relation_value querywhetheralready经generateassociateclose系
        $userIdRelationModel = $this->userIdRelationModel::query()
            ->where('delightful_id', $userIdRelationEntity->getAccountId())
            ->where('relation_type', $userIdRelationEntity->getRelationType())
            ->where('relation_value', $userIdRelationEntity->getRelationValue())
            ->where('id_type', $userIdRelationEntity->getIdType()->value);
        $relation = Db::select($userIdRelationModel->toSql(), $userIdRelationModel->getBindings())[0] ?? null;
        return is_array($relation) ? $relation : [];
    }

    // id_type,relation_type,relation_value query user_id,thengoqueryuserinformation
    public function getUerIdByRelation(DelightfulUserIdRelationEntity $userIdRelationEntity): string
    {
        $query = $this->userIdRelationModel::query()
            ->where('relation_type', $userIdRelationEntity->getRelationType())
            ->where('relation_value', $userIdRelationEntity->getRelationValue())
            ->where('id_type', $userIdRelationEntity->getIdType()->value);
        $userIdRelation = Db::select($query->toSql(), $query->getBindings())[0] ?? null;
        if (empty($userIdRelation)) {
            return '';
        }
        $idValue = $userIdRelation['id_value'] ?? '';
        $userIdRelationEntity->setIdValue($idValue);
        return $idValue;
    }
}
