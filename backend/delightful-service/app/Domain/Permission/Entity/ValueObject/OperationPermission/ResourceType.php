<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Permission\Entity\ValueObject\OperationPermission;

use App\ErrorCode\PermissionErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

enum ResourceType: int
{
    /**
     * AI 助理.
     */
    case AgentCode = 1;

    /**
     * 子流程.
     */
    case SubFlowCode = 2;

    /**
     * tool集.
     */
    case ToolSet = 3;

    /**
     * 知识库.
     */
    case Knowledge = 4;

    case MCPServer = 5;

    public static function make(mixed $type): ResourceType
    {
        if (! is_int($type)) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'common.invalid', ['label' => 'resource_type']);
        }
        $type = self::tryFrom($type);
        if (! $type) {
            ExceptionBuilder::throw(PermissionErrorCode::ValidateFailed, 'common.invalid', ['label' => 'resource_type']);
        }
        return $type;
    }
}
