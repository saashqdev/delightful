<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\ErrorCode;

use App\Infrastructure\Core\Exception\Annotation\ErrorMessage;

/**
 * 21000-21999.
 */
enum PluginErrorCode: int
{
    #[ErrorMessage('plugin.param_error')]
    case ParamError = 21000;

    #[ErrorMessage('plugin.not_found')]
    case PluginNotFound = 21001;
}
