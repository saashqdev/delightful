<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Interfaces\Agent\Facade\Sandbox;

use App\Infrastructure\Core\AbstractAuthApi;

abstract class AbstractBeDelightfulSandboxApi extends AbstractAuthApi
{
    protected function getGuardName(): string
    {
        return 'sandbox';
    }
}
