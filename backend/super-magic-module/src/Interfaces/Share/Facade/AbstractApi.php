<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\SuperMagic\Interfaces\Share\Facade;

use App\Infrastructure\Core\Traits\MagicUserAuthorizationTrait;

abstract class AbstractApi
{
    use MagicUserAuthorizationTrait;
}
