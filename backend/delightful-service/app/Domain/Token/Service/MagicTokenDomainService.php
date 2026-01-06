<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Token\Service;

use App\Domain\Token\Entity\DelightfulTokenEntity;
use App\Domain\Token\Repository\Facade\DelightfulTokenRepositoryInterface;

class DelightfulTokenDomainService
{
    public function __construct(
        protected DelightfulTokenRepositoryInterface $delightfulTokenRepository,
    ) {
    }

    public function createToken(DelightfulTokenEntity $tokenEntity): DelightfulTokenEntity
    {
        $this->delightfulTokenRepository->createToken($tokenEntity);
        return $tokenEntity;
    }

    public function getAccountId(DelightfulTokenEntity $tokenEntity): string
    {
        $this->delightfulTokenRepository->getTokenEntity($tokenEntity);
        return $tokenEntity->getTypeRelationValue();
    }

    public function getUserId(DelightfulTokenEntity $tokenEntity): string
    {
        $this->delightfulTokenRepository->getTokenEntity($tokenEntity);
        return $tokenEntity->getTypeRelationValue();
    }
}
