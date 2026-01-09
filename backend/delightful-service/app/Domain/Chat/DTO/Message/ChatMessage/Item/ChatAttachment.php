<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage\Item;

use App\Domain\Chat\Entity\AbstractEntity;
use App\Domain\Chat\Entity\ValueObject\FileType;

/**
 * attachmentnot是一种messagetype，而是message的一部分.
 */
class ChatAttachment extends AbstractEntity
{
    /**
     * chatfileneed先uploadto chat fileservice器，then才能sendmessage.
     * 这个 id 是 delightful_chat_file 表的primary key.
     */
    protected string $fileId = '';

    protected FileType $fileType = FileType::File;

    protected string $fileExtension = '';

    protected int $fileSize = 0;

    protected string $fileName = '';

    protected string $fileUrl;

    public function __construct(array $attachment = [])
    {
        parent::__construct($attachment);
    }

    public function getFileId(): string
    {
        return $this->fileId;
    }

    public function setFileId(string $fileId): void
    {
        $this->fileId = $fileId;
    }

    public function getFileType(): FileType
    {
        return $this->fileType;
    }

    public function setFileType(null|FileType|int $fileType): void
    {
        if ($fileType === null) {
            $this->fileType = FileType::File;
            return;
        }
        if (is_int($fileType)) {
            $this->fileType = FileType::from($fileType);
            return;
        }
        $this->fileType = $fileType;
    }

    public function getFileExtension(): string
    {
        return $this->fileExtension;
    }

    public function setFileExtension(string $fileExtension): void
    {
        $this->fileExtension = $fileExtension;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function setFileUrl(string $fileUrl): void
    {
        $this->fileUrl = $fileUrl;
    }

    public function getFileUrl(): string
    {
        return empty($this->fileUrl) ? '' : $this->fileUrl;
    }
}
