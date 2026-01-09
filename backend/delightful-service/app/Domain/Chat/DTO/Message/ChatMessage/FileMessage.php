<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage;

use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;

class FileMessage extends AbstractAttachmentMessage
{
    /**
     * getfileID（return第onefile的ID）
     * 对于单filemessage（如voice、video等）很有用.
     */
    public function getFileId(): ?string
    {
        $fileIds = $this->getFileIds();
        return ! empty($fileIds) ? $fileIds[0] : null;
    }

    /**
     * get第oneattachmentobject
     * 对于单attachmentmessage（如voice、video等）很有用.
     */
    public function getAttachment(): ?object
    {
        $attachments = $this->getAttachments();
        return ! empty($attachments) ? $attachments[0] : null;
    }

    protected function setMessageType(): void
    {
        $this->chatMessageType = ChatMessageType::File;
    }
}
