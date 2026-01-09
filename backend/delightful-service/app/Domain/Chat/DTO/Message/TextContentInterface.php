<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message;

/**
 * 从message中get文本content,用于大modelhistorymessagerecord等need纯文本的场景.
 */
interface TextContentInterface extends MessageInterface
{
    public function getTextContent(): string;
}
