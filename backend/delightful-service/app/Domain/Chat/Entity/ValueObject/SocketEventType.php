<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * 事件type.
 */
enum SocketEventType: string
{
    // connect
    case Connect = 'connect';

    // login. 以后登录can投一条控制message,来implement上线notify等逻辑
    case Login = 'login';

    // chatmessage
    case Chat = 'chat';

    // 控制message
    case Control = 'control';

    // streammessage
    case Stream = 'stream';

    /**
     * 实时性极高的过渡message，没有 seq_id，不will被持久化，也不will被cache。
     */
    case Intermediate = 'intermediate';
}
