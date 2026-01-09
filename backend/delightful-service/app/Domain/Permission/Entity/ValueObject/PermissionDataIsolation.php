<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Permission\Entity\ValueObject;

use App\Infrastructure\Core\DataIsolation\BaseDataIsolation;

/**
 * data隔离 SaaS化
 * 显type传入，防止隐type传入，导致not知道哪些placeneed做隔离.
 */
class PermissionDataIsolation extends BaseDataIsolation
{
    public static function create(string $currentOrganizationCode = '', string $userId = ''): self
    {
        return new self($currentOrganizationCode, $userId);
    }
}
