<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

use App\Infrastructure\Core\DataIsolation\BaseDataIsolation;

/**
 * 数据隔离 SaaS化
 * 显式传入，防止隐式传入，导致不知道哪些地方need做隔离.
 */
class KnowledgeBaseDataIsolation extends BaseDataIsolation
{
    public static function create(string $currentOrganizationCode = '', string $userId = '', string $delightfulId = ''): self
    {
        return new self($currentOrganizationCode, $userId, $delightfulId);
    }
}
