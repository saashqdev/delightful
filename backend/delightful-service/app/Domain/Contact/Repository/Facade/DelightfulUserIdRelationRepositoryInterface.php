<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Facade;

use App\Domain\Contact\Entity\DelightfulUserIdRelationEntity;

interface DelightfulUserIdRelationRepositoryInterface
{
    // 创建
    public function createUserIdRelation(DelightfulUserIdRelationEntity $userIdRelationEntity): void;

    // 查询
    public function getRelationIdExists(DelightfulUserIdRelationEntity $userIdRelationEntity): array;

    // id_type,relation_type,relation_value 查询 user_id,然后去查询userinformation
    public function getUerIdByRelation(DelightfulUserIdRelationEntity $userIdRelationEntity): string;
}
