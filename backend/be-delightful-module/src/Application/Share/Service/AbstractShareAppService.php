<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\Share\Service;

use App\Application\Kernel\AbstractKernelAppService;
use App\Infrastructure\Core\Traits\DataIsolationTrait;

/**
 * Abstract class for sharing application service.
 */
class AbstractShareAppService extends AbstractKernelAppService
{
    use DataIsolationTrait;
}
