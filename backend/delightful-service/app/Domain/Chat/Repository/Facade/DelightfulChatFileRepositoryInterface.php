<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Facade;

use App\Domain\Chat\Entity\DelightfulChatFileEntity;

interface DelightfulChatFileRepositoryInterface
{
    // useruploadfile
    public function uploadFile(DelightfulChatFileEntity $delightfulFileDTO): DelightfulChatFileEntity;

    /**
     * 批quantityuploadfile.
     * @param DelightfulChatFileEntity[] $delightfulFileDTOs
     * @return DelightfulChatFileEntity[]
     */
    public function uploadFiles(array $delightfulFileDTOs): array;

    /**
     * @return DelightfulChatFileEntity[]
     */
    public function getChatFileByIds(array $fileIds, ?string $order = null, ?int $limit = null): array;

    /**
     * passfile_keyfindfile.
     */
    public function getChatFileByFileKey(string $fileKey): ?DelightfulChatFileEntity;

    public function updateFile(DelightfulChatFileEntity $fileEntity): void;

    public function updateFileById(string $fileId, DelightfulChatFileEntity $entity);
}
