<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\File\Facade\Admin;

use App\Infrastructure\Core\Traits\DelightfulUserAuthorizationTrait;
use Hyperf\HttpServer\Contract\RequestInterface;

abstract class AbstractAdminApi
{
    use DelightfulUserAuthorizationTrait;

    public function __construct(
        protected readonly RequestInterface $request,
    ) {
    }
}
