<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperMagic\Domain\Agent\Repository\Persistence;

use App\Infrastructure\Core\AbstractRepository;

abstract class SuperMagicAbstractRepository extends AbstractRepository
{
    protected bool $filterOrganizationCode = true;
}
