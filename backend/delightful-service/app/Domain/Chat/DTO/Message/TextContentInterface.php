<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message;

/**
 * frommessage中get文本content,useat大modelhistorymessagerecordetcneed纯文本的场景.
 */
interface TextContentInterface extends MessageInterface
{
    public function getTextContent(): string;
}
