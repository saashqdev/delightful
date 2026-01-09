<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Constant;

use App\ErrorCode\AgentErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

use function Hyperf\Translation\__;

enum InstructGroupPosition: int
{
    case TOOLBAR = 1;    // tool栏
    case CHATBAR = 2;    // conversation栏

    public const MAX_INSTRUCTS = 5;

    public static function fromPosition(int $type): self
    {
        return match ($type) {
            self::TOOLBAR->value => self::TOOLBAR,
            self::CHATBAR->value => self::CHATBAR,
            default => ExceptionBuilder::throw(AgentErrorCode::VALIDATE_FAILED, 'agent.instruct_group_type_invalid'),
        };
    }

    /**
     * get所有组type及其国际化tag.
     * @return array<string, int> returntypename和对应的值
     */
    public static function getTypeOptions(): array
    {
        return [
            __('agent.instruct_group_type_toolbar') => self::TOOLBAR->value,
            __('agent.instruct_group_type_chatbar') => self::CHATBAR->value,
        ];
    }
}
