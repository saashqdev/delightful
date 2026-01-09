<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Service;

use App\Domain\Chat\DTO\Message\ChatFileInterface;
use App\Domain\Chat\DTO\Message\ChatMessage\Item\ChatAttachment;
use App\Domain\Chat\Entity\DelightfulChatFileEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

class DelightfulChatFileDomainService extends AbstractDomainService
{
    /**
     * @param DelightfulChatFileEntity[] $fileUploadDTOs
     * @return DelightfulChatFileEntity[]
     */
    public function fileUpload(array $fileUploadDTOs, DataIsolation $dataIsolation): array
    {
        $time = date('Y-m-d H:i:s');
        foreach ($fileUploadDTOs as $fileUploadDTO) {
            $fileUploadDTO->setUserId($dataIsolation->getCurrentUserId());
            $fileUploadDTO->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
            $fileUploadDTO->setCreatedAt($time);
            $fileUploadDTO->setUpdatedAt($time);
            $fileUploadDTO->setDelightfulMessageId('');
        }
        return $this->delightfulFileRepository->uploadFiles($fileUploadDTOs);
    }

    /**
     * 判断usermessagemiddle，whethercontain本time他想downloadfile.
     * @param DelightfulChatFileEntity[] $fileDTOs
     * @return DelightfulChatFileEntity[]
     */
    public function checkAndGetFilePaths(array $fileDTOs, DataIsolation $dataIsolation): array
    {
        // check message_id whetherhavethisfile
        $seqIds = array_column($fileDTOs, 'message_id');
        $seqList = $this->delightfulSeqRepository->batchGetSeqByMessageIds($seqIds);
        $delightfulMessageIdsMap = [];
        // checkuserwhether收tothisthesemessage
        foreach ($seqList as $seq) {
            if ($seq->getObjectId() !== $dataIsolation->getCurrentDelightfulId()) {
                continue;
            }
            // message_id => delightful_message_id
            $delightfulMessageIdsMap[$seq->getMessageId()] = $seq->getDelightfulMessageId();
        }
        $delightfulMessageIds = array_values($delightfulMessageIdsMap);
        if (empty($delightfulMessageIds)) {
            return [];
        }

        $tempMessagesEntities = $this->getMessageEntitiesByMaicMessageIds($delightfulMessageIds);
        $messageEntities = [];
        foreach ($tempMessagesEntities as $entity) {
            $messageEntities[$entity->getDelightfulMessageId()] = $entity;
        }

        // give $fileDTOs addup delightful_message_id
        foreach ($fileDTOs as $fileDTO) {
            $messageId = $fileDTO->getMessageId();
            /* @var DelightfulChatFileEntity $fileDTO */
            if (isset($delightfulMessageIdsMap[$messageId])) {
                $delightfulMessageId = $delightfulMessageIdsMap[$messageId];
                $fileDTO->setDelightfulMessageId($delightfulMessageId);
            }
        }

        // 判断usermessagemiddle，whethercontain本time他想downloadfile
        $fileMaps = [];
        foreach ($fileDTOs as $fileDTO) {
            $delightfulMessageId = $fileDTO->getDelightfulMessageId();
            $content = $messageEntities[$delightfulMessageId]->getContent();
            if (! $content instanceof ChatFileInterface) {
                continue;
            }
            $fileIdsInMessage = $content->getFileIds();
            if (in_array($fileDTO->getFileId(), $fileIdsInMessage)) {
                $fileMaps[] = $fileDTO;
            }
        }
        if (empty($fileMaps)) {
            return [];
        }
        $fileMapIds = array_column($fileMaps, 'file_id');
        return $this->getFileEntitiesByFileIds($fileMapIds);
    }

    /**
     * @return DelightfulChatFileEntity[]
     */
    public function getFileEntitiesByFileIds(array $fileIds, ?string $order = null, ?int $limit = null, bool $withUrl = false): array
    {
        // getfilepath
        $entities = $this->delightfulFileRepository->getChatFileByIds($fileIds, $order, $limit);
        if (! $withUrl) {
            return $entities;
        }
        foreach ($entities as $entity) {
            $fileLinks = $this->cloudFileRepository->getLinks($entity->getOrganizationCode(), [$entity->getFileKey()]);
            $fileKey = array_key_first($fileLinks);
            $fileLink = $fileLinks[$fileKey] ?? null;
            $entity->setExternalUrl($fileLink?->getUrl());
        }
        return $entities;
    }

    /**
     * saveorupdatefile
     * iffile_key已存in，thenupdatefileinfo
     * iffile_keynot存in，thencreate新file.
     *
     * @param DelightfulChatFileEntity $fileEntity file实body
     * @param DataIsolation $dataIsolation data隔离
     * @return DelightfulChatFileEntity saveorupdatebackfile实body
     */
    public function saveOrUpdateByFileKey(DelightfulChatFileEntity $fileEntity, DataIsolation $dataIsolation): DelightfulChatFileEntity
    {
        // passfile_keyfindfilewhether存in
        $existingFile = $this->delightfulFileRepository->getChatFileByFileKey($fileEntity->getFileKey());

        // iffile存in，updatefileinfo
        if ($existingFile) {
            $fileEntity->setFileId($existingFile->getFileId());
            $this->updateFile($fileEntity);
            return $fileEntity;
        }

        // iffilenot存in，create新file
        $time = date('Y-m-d H:i:s');
        $fileEntity->setUserId($dataIsolation->getCurrentUserId());
        $fileEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $fileEntity->setCreatedAt($time);
        $fileEntity->setUpdatedAt($time);
        $fileEntity->setDelightfulMessageId('');

        return $this->delightfulFileRepository->uploadFile($fileEntity);
    }

    /**
     * 判断usermessagemiddleattachmentisnotis他from己upload.
     * @param ChatAttachment[] $attachments
     * @return ChatAttachment[]
     */
    public function checkAndFillAttachments(array $attachments, DataIsolation $dataIsolation): array
    {
        $fileIds = array_column($attachments, 'file_id');
        $fileEntities = $this->getFileEntitiesByFileIds($fileIds);
        $fileEntities = array_column($fileEntities, null, 'file_id');
        // todo ifmessagemiddlehavefile:1.判断file所have者whetheriscurrentuser;2.判断userwhetherreceivepassthisthesefile。
        //        foreach ($fileEntities as $fileEntity) {
        //            if ($fileEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
        //                ExceptionBuilder::throw(ChatErrorCode::FILE_NOT_FOUND);
        //            }
        //        }

        foreach ($attachments as $attachment) {
            $fileId = $attachment->getFileId();
            $fileEntity = $fileEntities[$fileId] ?? null;
            if ($fileEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::FILE_NOT_FOUND);
            }
            $attachment->setFileExtension($fileEntity->getFileExtension());
            $attachment->setFileSize($fileEntity->getFileSize());
            $attachment->setFileName($fileEntity->getFileName());
            $attachment->setFileType($fileEntity->getFileType());
        }
        return $attachments;
    }

    public function updateFile(DelightfulChatFileEntity $fileEntity): void
    {
        $this->delightfulFileRepository->updateFile($fileEntity);
    }

    public function updateFileById(string $fileId, DelightfulChatFileEntity $data): void
    {
        $this->delightfulFileRepository->updateFileById($fileId, $data);
    }
}
