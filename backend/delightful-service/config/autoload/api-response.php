<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\ErrorCode\GenericErrorCode;
use App\ErrorCode\HttpErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use BeDelightful\ApiResponse\Response\LowCodeResponse;
use BeDelightful\ApiResponse\Response\StandardResponse;
use Hyperf\Validation\ValidationException;
use Qbhy\HyperfAuth\Exception\UnauthorizedException;

return [
    'default' => [
        'version' => 'standard',
    ],
    // AOP handler will automatically catch exceptions configured here and return error structure (implementation class must inherit Exception).
    'error_exception' => [
        BusinessException::class,
        UnauthorizedException::class => static function (UnauthorizedException $exception) {
            return [
                'code' => HttpErrorCode::Unauthorized->value,
                'message' => $exception->getMessage(),
            ];
        },
        ValidationException::class => static function (ValidationException $exception) {
            return [
                'code' => GenericErrorCode::ParameterValidationFailed->value,
                'message' => $exception->validator->errors()->first(),
            ];
        },
    ],

    'version' => [
        'standard' => StandardResponse::class,
        'low_code' => LowCodeResponse::class,
    ],
];
