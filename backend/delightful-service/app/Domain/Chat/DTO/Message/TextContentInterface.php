<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message;

/**
 * 从message中get文本内容,用于大模型历史message记录等need纯文本的场景.
 */
interface TextContentInterface extends MessageInterface
{
    public function getTextContent(): string;
}
