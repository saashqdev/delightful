<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Environment;

class EnvironmentUtil
{
    public const string ENV_LOCAL = 'local';

    public const string ENV_LOCAL_DEV = 'local-dev';

    // whether本ground环境
    public static function isLocal(): bool
    {
        if (in_array(self::getEnv(), [self::ENV_LOCAL, self::ENV_LOCAL_DEV])) {
            return true;
        }
        return false;
    }

    public static function getEnv(): string
    {
        return env('APP_ENV');
    }
}
