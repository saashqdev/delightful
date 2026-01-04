<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\SuperMagic\Interfaces\Agent\Facade\Sandbox;

use App\Infrastructure\Core\AbstractAuthApi;

abstract class AbstractSuperMagicSandboxApi extends AbstractAuthApi
{
    protected function getGuardName(): string
    {
        return 'sandbox';
    }
}
