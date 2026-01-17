<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\Agent\Facade\Admin;

use App\Infrastructure\Core\AbstractAuthApi;

class AbstractBeDelightfulAdminApi extends AbstractAuthApi
{
    protected function getGuardName(): string
    {
        return 'web';
    }
}
