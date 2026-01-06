<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Facade;

use App\Infrastructure\Core\Traits\DelightfulUserAuthorizationTrait;

abstract class AbstractApi
{
    use DelightfulUserAuthorizationTrait;
}
