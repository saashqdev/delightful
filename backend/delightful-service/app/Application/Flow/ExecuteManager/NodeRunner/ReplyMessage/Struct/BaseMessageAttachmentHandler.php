<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\ReplyMessage\Struct;

class BaseMessageAttachmentHandler implements MessageAttachmentHandlerInterface
{
    public function handle(string $content, bool $markdownImageFormat = false): string
    {
        return $content;
    }
}
