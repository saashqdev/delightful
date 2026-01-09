<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Event\Subscribe\Agent\Factory;

use App\Application\Chat\Event\Subscribe\Agent\Agents\AgentInterface;
use App\Application\Chat\Event\Subscribe\Agent\Agents\DefaultAgent;

class AgentFactory
{
    public static function make(string $aiCode): AgentInterface
    {
        // 暂noneed硬encoding助理,back续havecan复usethefactorylogiccreate硬encoding助理
        /* @phpstan-ignore-next-line */
        return match ($aiCode) {
            default => di(DefaultAgent::class),
        };
    }
}
