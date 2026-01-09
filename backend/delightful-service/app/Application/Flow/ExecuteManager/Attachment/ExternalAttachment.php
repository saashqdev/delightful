<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Attachment;

use App\Infrastructure\Util\FileType;

/**
 * outside链.
 */
class ExternalAttachment extends AbstractAttachment
{
    public function __construct(string $url)
    {
        $this->url = $url;
        $this->ext = FileType::getType($url);
        $this->size = 0;
        $this->name = basename($url);
        $this->originAttachment = $url;
    }
}
