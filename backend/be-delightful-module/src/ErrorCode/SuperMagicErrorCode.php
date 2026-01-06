<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\ErrorCode;

use App\Infrastructure\Core\Exception\Annotation\ErrorMessage;

enum BeDelightfulErrorCode: int
{
    #[ErrorMessage(message: 'be_delightful.validate_failed')]
    case ValidateFailed = 60001;

    #[ErrorMessage(message: 'be_delightful.not_found')]
    case NotFound = 60002;

    #[ErrorMessage(message: 'be_delightful.save_failed')]
    case SaveFailed = 60003;

    #[ErrorMessage(message: 'be_delightful.delete_failed')]
    case DeleteFailed = 60004;

    #[ErrorMessage(message: 'be_delightful.operation_failed')]
    case OperationFailed = 60005;

    #[ErrorMessage(message: 'be_delightful.agent.limit_exceeded')]
    case AgentLimitExceeded = 60006;

    #[ErrorMessage(message: 'be_delightful.agent.builtin_not_allowed')]
    case BuiltinAgentNotAllowed = 60007;
}
