<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\Chat\Entity\DelightfulChatFileEntity;
use App\Domain\Chat\Entity\ValueObject\FileType;
use App\Domain\Chat\Service\DelightfulChatFileDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\File\Service\FileDomainService;

/**
 * chatfileapplicationservice
 * providegiveother领域useinterface.
 */
class DelightfulChatFileAppService extends AbstractAppService
{
    public function __construct(
        private readonly DelightfulChatFileDomainService $delightfulChatFileDomainService,
        private readonly FileDomainService $fileDomainService
    ) {
    }

    /**
     * passfile_keysaveorupdatefile
     * iffilealready存inthenupdate，not存inthencreate.
     *
     * @param string $fileKey filekey
     * @param DataIsolation $dataIsolation data隔离object
     * @param array $fileData filedata
     * @return array returncontainfileinfoarray
     */
    public function saveOrUpdateByFileKey(string $fileKey, DataIsolation $dataIsolation, array $fileData): array
    {
        // 1. 准备file实body
        $fileEntity = new DelightfulChatFileEntity();
        $fileEntity->setFileKey($fileKey);
        $fileEntity->setFileExtension($fileData['file_extension'] ?? '');
        $fileEntity->setFileName($fileData['filename'] ?? '');
        $fileEntity->setFileSize($fileData['file_size'] ?? 0);

        // processfiletype
        $fileTypeValue = $fileData['file_type'] ?? FileType::File->value;
        $fileType = FileType::tryFrom($fileTypeValue) ?? FileType::File;
        $fileEntity->setFileType($fileType);

        // 2. saveorupdatefile
        $savedFile = $this->delightfulChatFileDomainService->saveOrUpdateByFileKey($fileEntity, $dataIsolation);

        // 3. getfileURL
        $fileUrl = $this->fileDomainService->getLink(
            $dataIsolation->getCurrentOrganizationCode(),
            $fileKey
        )?->getUrl() ?? '';

        // 4. returnfileinfo
        return [
            'file_id' => $savedFile->getFileId(),
            'file_key' => $savedFile->getFileKey(),
            'file_extension' => $savedFile->getFileExtension(),
            'file_name' => $savedFile->getFileName(),
            'file_size' => $savedFile->getFileSize(),
            'file_type' => $savedFile->getFileType()->value,
            'external_url' => $fileUrl,
        ];
    }

    /**
     * getfileinfo.
     *
     * @param string $fileId fileID
     * @return null|array fileinfo
     */
    public function getFileInfo(string $fileId): ?array
    {
        // passIDgetfile实body
        $fileEntities = $this->delightfulChatFileDomainService->getFileEntitiesByFileIds([$fileId], null, null, true);
        if (empty($fileEntities)) {
            return null;
        }

        $fileEntity = $fileEntities[0];

        // returnfileinfoarray
        return [
            'file_id' => $fileEntity->getFileId(),
            'file_key' => $fileEntity->getFileKey(),
            'file_extension' => $fileEntity->getFileExtension(),
            'file_name' => $fileEntity->getFileName(),
            'file_size' => $fileEntity->getFileSize(),
            'file_type' => $fileEntity->getFileType()->value,
            'external_url' => $fileEntity->getExternalUrl(),
        ];
    }
}
