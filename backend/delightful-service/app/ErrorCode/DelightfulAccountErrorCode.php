<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\ErrorCode;

use App\Infrastructure\Core\Exception\Annotation\ErrorMessage;

/**
 * Error code range: [4100, 4300].
 */
enum DelightfulAccountErrorCode: int
{
    // Login type not supported
    #[ErrorMessage('account.login_type_not_support')]
    case LOGIN_TYPE_NOT_SUPPORT = 4100;

    // Account registration failed
    #[ErrorMessage('account.register_failed')]
    case REGISTER_FAILED = 4101;

    // Request too frequent
    #[ErrorMessage('account.request_too_frequent')]
    case REQUEST_TOO_FREQUENT = 4102;

    // Login not supported for current environment
    #[ErrorMessage('account.login_env_not_support')]
    case LOGIN_ENV_NOT_SUPPORT = 4103;
}
