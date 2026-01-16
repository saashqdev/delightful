<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Domain\Agent\Repository\Persistence;

use App\Infrastructure\Core\AbstractRepository;

abstract class BeDelightfulAbstractRepository extends AbstractRepository
{
    protected bool $filterOrganizationCode = true;
}
