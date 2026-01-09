<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\File;

use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\AbstractMention;
use App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention\MentionType;

final class ProjectFileMention extends AbstractMention
{
    public function getMentionTextStruct(): string
    {
        // iffile_pathfornull,needaccording to file_id拿to file_key,from file_keyparseto file_path
        $data = $this->getAttrs()?->getData();
        if (! $data instanceof FileData) {
            return '';
        }
        $filePath = $data->getFilePath() ?? '';
        return sprintf('[@file_path:%s]', $filePath);
    }

    public function getMentionJsonStruct(): array
    {
        $data = $this->getAttrs()?->getData();
        if (! $data instanceof FileData) {
            return [];
        }

        return [
            'type' => MentionType::PROJECT_FILE->value,
            'file_id' => $data->getFileId(),
            'file_key' => $data->getFileKey(),
            'file_path' => $data->getFilePath(),
            'file_name' => $data->getFileName(),
        ];
    }
}
