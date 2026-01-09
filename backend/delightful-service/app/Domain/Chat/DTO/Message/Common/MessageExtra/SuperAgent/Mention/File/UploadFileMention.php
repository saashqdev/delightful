<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\File;

use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\AbstractMention;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\MentionType;

final class UploadFileMention extends AbstractMention
{
    public function getMentionTextStruct(): string
    {
        $data = $this->getAttrs()?->getData();
        if (! $data instanceof FileData) {
            return '';
        }
        // @todo uploadfile目frontdirectly放inwork区rootdirectory.backsurfacemaybewilladjustpath,too clockagain改.
        $filePath = $data->getFileName() ?? '';
        return sprintf('@<file_path>%s</file_path>', $filePath);
    }

    public function getMentionJsonStruct(): array
    {
        $data = $this->getAttrs()?->getData();
        if (! $data instanceof FileData) {
            return [];
        }

        return [
            'type' => MentionType::UPLOAD_FILE->value,
            'file_id' => $data->getFileId(),
            'file_key' => $data->getFileKey(),
            'file_path' => $data->getFilePath(),
            'file_name' => $data->getFileName(),
            'file_size' => $data->getFileSize(),
        ];
    }
}
