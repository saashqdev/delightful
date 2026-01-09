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
     * processusercall Agent 的event.
     */
    public function process(UserCallAgentEvent $event): void;

    /**
     * 判断when前process器是否canprocess该 AI Code.
     */
    public function canHandle(string $aiCode): bool;

    /**
     * getprocess器优先级.
     *
     * number越大优先级越高，default为0
     * 企业版canreturn更高的优先级以覆盖defaultimplement
     */
    public static function getPriority(): int;
}
