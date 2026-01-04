<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Infrastructure\ExternalAPI\MagicAIApi\Kernel;

abstract class AbstractRequest
{
    abstract public function toBody(): array;
}
