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
     * judgewhenfrontprocess器whethercanprocessthe AI Code.
     */
    public function canHandle(string $aiCode): bool;

    /**
     * getprocess器优先level.
     *
     * numbermorebig优先levelmorehigh，defaultfor0
     * 企业版canreturnmorehigh优先levelby覆盖defaultimplement
     */
    public static function getPriority(): int;
}
