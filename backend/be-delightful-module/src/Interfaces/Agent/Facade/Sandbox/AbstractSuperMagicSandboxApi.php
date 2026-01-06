<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperDelightful\Interfaces\Agent\Facade\Sandbox;

use App\Infrastructure\Core\AbstractAuthApi;

abstract class AbstractSuperDelightfulSandboxApi extends AbstractAuthApi
{
    protected function getGuardName(): string
    {
        return 'sandbox';
    }
}
