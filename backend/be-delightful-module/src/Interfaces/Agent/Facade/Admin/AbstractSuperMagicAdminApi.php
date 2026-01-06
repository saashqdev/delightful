<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperDelightful\Interfaces\Agent\Facade\Admin;

use App\Infrastructure\Core\AbstractAuthApi;

class AbstractSuperDelightfulAdminApi extends AbstractAuthApi
{
    protected function getGuardName(): string
    {
        return 'web';
    }
}
