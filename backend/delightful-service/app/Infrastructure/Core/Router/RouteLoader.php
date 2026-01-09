<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Router;

class RouteLoader
{
    public static function loadDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        // getdirectory下的所have*php
        $files = glob($dir . '/*.php');
        foreach ($files as $file) {
            self::loadPath($file);
        }
    }

    public static function loadPath(string $path): void
    {
        if (file_exists($path)) {
            require_once $path;
        }
    }
}
