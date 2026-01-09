<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * eventtype.
 */
enum SocketEventType: string
{
    // connect
    case Connect = 'connect';

    // login. by后logincan投一条控制message,来implement上线notifyetc逻辑
    case Login = 'login';

    // chatmessage
    case Chat = 'chat';

    // 控制message
    case Control = 'control';

    // streammessage
    case Stream = 'stream';

    /**
     * 实时性极高的过渡message，nothave seq_id，notwillbe持久化，alsonotwillbecache。
     */
    case Intermediate = 'intermediate';
}
