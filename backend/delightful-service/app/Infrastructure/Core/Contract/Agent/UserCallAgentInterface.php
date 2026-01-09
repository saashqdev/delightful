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
     * processusercall Agent event.
     */
    public function process(UserCallAgentEvent $event): void;

    /**
     * judgewhenfrontprocessdevicewhethercanprocessthe AI Code.
     */
    public function canHandle(string $aiCode): bool;

    /**
     * getprocessdeviceprioritylevel.
     *
     * numbermorebigprioritylevelmorehigh,defaultfor0
     * enterpriseversioncanreturnmorehighprioritylevelbycoveragedefaultimplement
     */
    public static function getPriority(): int;
}
