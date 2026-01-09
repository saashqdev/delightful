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
     * get文件ID（return第one文件的ID）
     * 对于单文件message（如语音、视频等）很有用.
     */
    public function getFileId(): ?string
    {
        $fileIds = $this->getFileIds();
        return ! empty($fileIds) ? $fileIds[0] : null;
    }

    /**
     * get第one附件对象
     * 对于单附件message（如语音、视频等）很有用.
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
