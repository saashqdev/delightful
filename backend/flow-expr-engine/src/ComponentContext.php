<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine;

use Delightful\SdkBase\SdkBase;
use Delightful\SdkBase\SdkBaseContext;

class ComponentContext
{
    public static function register(SdkBase $sdkBase): void
    {
        SdkBaseContext::register(SdkInfo::NAME, $sdkBase);
    }

    public static function getSdkContainer(): SdkBase
    {
        return SdkBaseContext::get(SdkInfo::NAME);
    }

    public static function hasSdkContainer(): bool
    {
        return SdkBaseContext::has(SdkInfo::NAME);
    }
}
