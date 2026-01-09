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

    // login. bybacklogincan投oneitemcontrolmessage,comeimplementuplinenotifyetc逻辑
    case Login = 'login';

    // chatmessage
    case Chat = 'chat';

    // controlmessage
    case Control = 'control';

    // streammessage
    case Stream = 'stream';

    /**
     * 实o clockproperty极hightransitionmessage，nothave seq_id，notwillbe持久化，alsonotwillbecache。
     */
    case Intermediate = 'intermediate';
}
