<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject\MessageType;

/**
 * message的optionalitem.
 */
enum MessageOptionsEnum: string
{
    //  message的optionalitem
    case EDIT_MESSAGE_OPTIONS = 'edit_message_options';
}
