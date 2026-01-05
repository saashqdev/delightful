<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\Kernel\Contracts\Endpoint;

interface EndpointInterface
{
    public function selectApp(string $appName): void;
}
