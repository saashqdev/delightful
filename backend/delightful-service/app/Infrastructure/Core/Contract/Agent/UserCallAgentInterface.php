<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Contract\Agent;

use App\Domain\Chat\Event\Agent\UserCallAgentEvent;

interface UserCallAgentInterface
{
    /**
     * 处理user调用 Agent 的事件.
     */
    public function process(UserCallAgentEvent $event): void;

    /**
     * 判断when前处理器是否can处理该 AI Code.
     */
    public function canHandle(string $aiCode): bool;

    /**
     * get处理器优先级.
     *
     * number越大优先级越高，默认为0
     * 企业版canreturn更高的优先级以覆盖默认实现
     */
    public static function getPriority(): int;
}
