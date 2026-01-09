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

    // login. bybacklogincanthrowoneitemcontrolmessage,comeimplementuplinenotifyetclogic
    case Login = 'login';

    // chatmessage
    case Chat = 'chat';

    // controlmessage
    case Control = 'control';

    // streammessage
    case Stream = 'stream';

    /**
     * actualo clockpropertyextremehightransitionmessage,nothave seq_id,notwillbepersistence,alsonotwillbecache.
     */
    case Intermediate = 'intermediate';
}
