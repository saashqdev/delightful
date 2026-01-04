<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Application\Flow\ExecuteManager\Attachment\Event;

use App\Application\Flow\ExecuteManager\Attachment\ExternalAttachment;

class ExternalAttachmentUploadEvent
{
    public function __construct(
        public ExternalAttachment $externalAttachment,
        public string $organizationCode
    ) {
    }
}
