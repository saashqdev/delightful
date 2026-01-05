<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\TaskScheduler\Util;

use function Hyperf\Config\config;

class Functions
{
    public static function getEnv(): string
    {
        $appEnv = '';
        if (! config('task_scheduler.environment_enabled', true)) {
            return '';
        }
        if (function_exists('app_env')) {
            $appEnv = app_env();
        }
        if (empty($appEnv)) {
            return '';
        }
        return $appEnv;
    }
}
