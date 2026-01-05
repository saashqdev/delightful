<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Token\Repository\Facade;

use App\Domain\Token\Entity\MagicTokenEntity;
use App\Domain\Token\Entity\ValueObject\MagicTokenType;

interface MagicTokenRepositoryInterface
{
    /**
     * Retrieve the entity related to a token (for example, which magic_id a token belongs to).
     */
    public function getTokenEntity(MagicTokenEntity $tokenDTO): ?MagicTokenEntity;

    public function createToken(MagicTokenEntity $tokenDTO): void;

    public function getTokenByTypeAndRelationValue(MagicTokenType $type, string $relationValue): ?MagicTokenEntity;

    public function deleteToken(MagicTokenEntity $tokenDTO): void;
}
