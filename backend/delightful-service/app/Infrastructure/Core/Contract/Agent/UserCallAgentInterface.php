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
     * 判断whenfrontprocess器whethercanprocess该 AI Code.
     */
    public function canHandle(string $aiCode): bool;

    /**
     * getprocess器优先level.
     *
     * numbermore大优先levelmore高，default为0
     * 企业版canreturnmore高的优先levelby覆盖defaultimplement
     */
    public static function getPriority(): int;
}
