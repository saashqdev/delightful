<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

enum AmqpTopicType: string
{
    // productionmessage(consumeeachtypecustomerclientproducemessage,generatesequencecolumnnumber)
    case Message = 'delightful-chat-message';

    // delivermessage(consumesequencecolumnnumber)
    case Seq = 'delightful-chat-seq';

    // recordingmessage
    case Recording = 'delightful-chat-recording';
}
