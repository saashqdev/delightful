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
        return $this->magicFileRepository->uploadFiles($fileUploadDTOs);
    }

    /**
     * 判断用户的消息中，是否包含本次他想下载的文件.
     * @param DelightfulChatFileEntity[] $fileDTOs
     * @return DelightfulChatFileEntity[]
     */
    public function checkAndGetFilePaths(array $fileDTOs, DataIsolation $dataIsolation): array
    {
        // 检查 message_id 是否有此文件
        $seqIds = array_column($fileDTOs, 'message_id');
        $seqList = $this->magicSeqRepository->batchGetSeqByMessageIds($seqIds);
        $magicMessageIdsMap = [];
        // 检查用户是否收到了这些消息
        foreach ($seqList as $seq) {
            if ($seq->getObjectId() !== $dataIsolation->getCurrentDelightfulId()) {
                continue;
            }
            // message_id => magic_message_id
            $magicMessageIdsMap[$seq->getMessageId()] = $seq->getDelightfulMessageId();
        }
        $magicMessageIds = array_values($magicMessageIdsMap);
        if (empty($magicMessageIds)) {
            return [];
        }

        $tempMessagesEntities = $this->getMessageEntitiesByMaicMessageIds($magicMessageIds);
        $messageEntities = [];
        foreach ($tempMessagesEntities as $entity) {
            $messageEntities[$entity->getDelightfulMessageId()] = $entity;
        }

        // 给 $fileDTOs 加上 magic_message_id
        foreach ($fileDTOs as $fileDTO) {
            $messageId = $fileDTO->getMessageId();
            /* @var DelightfulChatFileEntity $fileDTO */
            if (isset($magicMessageIdsMap[$messageId])) {
                $magicMessageId = $magicMessageIdsMap[$messageId];
                $fileDTO->setDelightfulMessageId($magicMessageId);
            }
        }

        // 判断用户的消息中，是否包含本次他想下载的文件
        $fileMaps = [];
        foreach ($fileDTOs as $fileDTO) {
            $magicMessageId = $fileDTO->getDelightfulMessageId();
            $content = $messageEntities[$magicMessageId]->getContent();
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
        // 获取文件路径
        $entities = $this->magicFileRepository->getChatFileByIds($fileIds, $order, $limit);
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
     * 保存或更新文件
     * 如果file_key已存在，则更新文件信息
     * 如果file_key不存在，则创建新文件.
     *
     * @param DelightfulChatFileEntity $fileEntity 文件实体
     * @param DataIsolation $dataIsolation 数据隔离
     * @return DelightfulChatFileEntity 保存或更新后的文件实体
     */
    public function saveOrUpdateByFileKey(DelightfulChatFileEntity $fileEntity, DataIsolation $dataIsolation): DelightfulChatFileEntity
    {
        // 通过file_key查找文件是否存在
        $existingFile = $this->magicFileRepository->getChatFileByFileKey($fileEntity->getFileKey());

        // 如果文件存在，更新文件信息
        if ($existingFile) {
            $fileEntity->setFileId($existingFile->getFileId());
            $this->updateFile($fileEntity);
            return $fileEntity;
        }

        // 如果文件不存在，创建新文件
        $time = date('Y-m-d H:i:s');
        $fileEntity->setUserId($dataIsolation->getCurrentUserId());
        $fileEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $fileEntity->setCreatedAt($time);
        $fileEntity->setUpdatedAt($time);
        $fileEntity->setDelightfulMessageId('');

        return $this->magicFileRepository->uploadFile($fileEntity);
    }

    /**
     * 判断用户的消息中的附件是不是他自己上传的.
     * @param ChatAttachment[] $attachments
     * @return ChatAttachment[]
     */
    public function checkAndFillAttachments(array $attachments, DataIsolation $dataIsolation): array
    {
        $fileIds = array_column($attachments, 'file_id');
        $fileEntities = $this->getFileEntitiesByFileIds($fileIds);
        $fileEntities = array_column($fileEntities, null, 'file_id');
        // todo 如果消息中有文件:1.判断文件的所有者是否是当前用户;2.判断用户是否接收过这些文件。
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
        $this->magicFileRepository->updateFile($fileEntity);
    }

    public function updateFileById(string $fileId, DelightfulChatFileEntity $data): void
    {
        $this->magicFileRepository->updateFileById($fileId, $data);
    }
}
