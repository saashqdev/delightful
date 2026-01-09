<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Attachment;

use App\Infrastructure\Util\FileType;

/**
 * 这里的attachment一定是已经在云服务端了.
 */
class Attachment extends AbstractAttachment
{
    public function __construct(
        string $name,
        string $url,
        string $ext,
        int $size,
        string $chatFileId = '',
        string $originAttachment = ''
    ) {
        $this->originAttachment = $originAttachment;
        $this->name = $name;
        $this->size = $size;
        $this->chatFileId = $chatFileId;
        $this->url = trim($url);
        // if没有 ext，从 url 中提取
        if (empty($this->ext)) {
            $this->ext = FileType::getType($this->url);
        } else {
            $this->ext = $ext;
        }
    }
}
