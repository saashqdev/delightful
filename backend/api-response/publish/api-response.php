<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Dtyq\ApiResponse\Response\LowCodeResponse;
use Dtyq\ApiResponse\Response\StandardResponse;

return [
    'default' => [
        'version' => 'standard',
    ],
    // AOP processor will automatically catch exceptions configured here and return error structure (implementation class must inherit Exception).
    'error_exception' => [
        Exception::class,
    ],
    'version' => [
        'standard' => StandardResponse::class,
        'low_code' => LowCodeResponse::class,
    ],
];
