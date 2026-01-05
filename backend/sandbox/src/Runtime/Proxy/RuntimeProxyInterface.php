<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace PHPSandbox\Runtime\Proxy;

use PHPSandbox\Options\SandboxOptions;

interface RuntimeProxyInterface
{
    public function setOptions(SandboxOptions $options): self;
}
