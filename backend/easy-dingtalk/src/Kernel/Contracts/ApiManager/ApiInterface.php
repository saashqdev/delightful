<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\Kernel\Contracts\ApiManager;

use Dtyq\SdkBase\Kernel\Constant\RequestMethod;

interface ApiInterface
{
    public function getMethod(): RequestMethod;

    public function getHost(): string;

    public function getUri(): string;

    public function getOptions(): array;

    public function getPathParams(): array;
}
