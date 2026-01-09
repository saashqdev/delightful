<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Facade;

use App\Domain\Contact\Entity\DelightfulUserIdRelationEntity;

interface DelightfulUserIdRelationRepositoryInterface
{
    // create
    public function createUserIdRelation(DelightfulUserIdRelationEntity $userIdRelationEntity): void;

    // query
    public function getRelationIdExists(DelightfulUserIdRelationEntity $userIdRelationEntity): array;

    // id_type,relation_type,relation_value query user_id,然后去queryuserinformation
    public function getUerIdByRelation(DelightfulUserIdRelationEntity $userIdRelationEntity): string;
}
