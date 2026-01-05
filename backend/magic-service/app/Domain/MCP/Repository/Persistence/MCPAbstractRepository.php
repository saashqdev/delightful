<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\MCP\Repository\Persistence;

use App\Infrastructure\Core\AbstractRepository;

abstract class MCPAbstractRepository extends AbstractRepository
{
    protected bool $filterOrganizationCode = true;
}
