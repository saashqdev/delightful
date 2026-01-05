<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Core\Router\RouteLoader;

RouteLoader::loadDir(BASE_PATH . '/vendor/dtyq/super-magic-module/config/routes-v1');
