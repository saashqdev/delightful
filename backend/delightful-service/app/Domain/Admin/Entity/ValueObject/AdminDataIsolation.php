<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Admin\Entity\ValueObject;

use App\Infrastructure\Core\DataIsolation\BaseDataIsolation;

/**
 * dataisolation SaaS化
 * 显typepass in,prevent隐typepass in,导致notknow哪theseplaceneed做isolation.
 */
class AdminDataIsolation extends BaseDataIsolation
{
    public static function create(string $currentOrganizationCode = '', string $userId = '', string $delightfulId = ''): self
    {
        return new self($currentOrganizationCode, $userId, $delightfulId);
    }
}
