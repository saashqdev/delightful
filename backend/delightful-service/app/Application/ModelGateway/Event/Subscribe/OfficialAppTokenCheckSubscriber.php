<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\ModelGateway\Event\Subscribe;

use App\Application\ModelGateway\Official\DelightfulAccessToken;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Server\Event\MainCoroutineServerStart;

/**
 * 官方应用check.
 */
#[Listener]
class OfficialAppTokenCheckSubscriber implements ListenerInterface
{
    public function listen(): array
    {
        return [
            MainCoroutineServerStart::class,
        ];
    }

    public function process(object $event): void
    {
        DelightfulAccessToken::init();
    }
}
