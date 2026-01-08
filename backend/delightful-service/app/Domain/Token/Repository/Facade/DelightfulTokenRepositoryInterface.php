<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Token\Repository\Facade;

use App\Domain\Token\Entity\DelightfulTokenEntity;
use App\Domain\Token\Entity\ValueObject\DelightfulTokenType;

interface DelightfulTokenRepositoryInterface
{
    /**
     * Retrieve the entity related to a token (for example, which delightful_id a token belongs to).
     */
    public function getTokenEntity(DelightfulTokenEntity $tokenDTO): ?DelightfulTokenEntity;

    public function createToken(DelightfulTokenEntity $tokenDTO): void;

    public function getTokenByTypeAndRelationValue(DelightfulTokenType $type, string $relationValue): ?DelightfulTokenEntity;

    public function deleteToken(DelightfulTokenEntity $tokenDTO): void;
}
