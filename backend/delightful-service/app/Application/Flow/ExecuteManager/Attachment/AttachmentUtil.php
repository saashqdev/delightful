<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\Attachment;

use App\Domain\Chat\DTO\Message\ChatMessage\AbstractAttachmentMessage;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Service\DelightfulChatFileDomainService;
use App\Domain\File\Service\FileDomainService;

class AttachmentUtil
{
    /**
     * @return array<Attachment>
     */
    public static function getByDelightfulMessageEntity(DelightfulMessageEntity $messageEntity): array
    {
        $attachments = [];
        $messageContent = $messageEntity->getContent();
        if ($messageContent instanceof AbstractAttachmentMessage) {
            $delightfulChatFileDomainService = di(DelightfulChatFileDomainService::class);
            $fileDomainService = di(FileDomainService::class);

            $chatFiles = $delightfulChatFileDomainService->getFileEntitiesByFileIds($messageContent->getFileIds());
            $fileOrgPaths = [];
            $chatFilesMaps = [];
            foreach ($chatFiles as $chatFile) {
                $fileOrgPaths[$chatFile->getOrganizationCode()][] = $chatFile->getFileKey();
                $chatFilesMaps[$chatFile->getFileKey()] = $chatFile;
            }
            foreach ($fileOrgPaths as $organizationCode => $filePaths) {
                $fileLinks = $fileDomainService->getLinks($organizationCode, $filePaths);
                foreach ($fileLinks as $fileLink) {
                    if (! $chatFile = $chatFilesMaps[$fileLink->getPath()] ?? null) {
                        continue;
                    }
                    $attachments[] = new Attachment(
                        name: $chatFile->getFileName(),
                        url: $fileLink->getUrl(),
                        ext: $chatFile->getFileExtension(),
                        size: $chatFile->getFileSize(),
                        chatFileId: $chatFile->getFileId() ?? '',
                    );
                }
            }
        }
        return $attachments;
    }

    public static function getByApiArray(array $data): array
    {
        $attachments = [];
        foreach ($data as $datum) {
            if (empty($datum['attachment_url']) && empty($datum['url'])) {
                continue;
            }
            $attachments[] = new Attachment(
                name: ($datum['attachment_name'] ?? $datum['name'] ?? ''),
                url: ($datum['attachment_url'] ?? $datum['url'] ?? ''),
                ext: ($datum['attachment_ext'] ?? $datum['extension'] ?? ''),
                size: (int) ($datum['attachment_size'] ?? $datum['size'] ?? ''),
            );
        }
        return $attachments;
    }
}
