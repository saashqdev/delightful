<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperDelightful\Infrastructure\ExternalAPI\SandboxOS\Contract;

interface RequestInterface
{
    public function toArray(): array;
}
