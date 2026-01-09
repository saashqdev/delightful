<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Facade;

use App\Domain\Chat\DTO\ConversationListQueryDTO;
use App\Domain\Chat\DTO\PageResponseDTO\ConversationsPageResponseDTO;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationStatus;
use App\Domain\Chat\Entity\ValueObject\ConversationType;

interface DelightfulChatConversationRepositoryInterface
{
    public function getConversationsByUserIds(DelightfulConversationEntity $conversation, ConversationListQueryDTO $queryDTO, array $userIds): ConversationsPageResponseDTO;

    public function getConversationByUserIdAndReceiveId(DelightfulConversationEntity $conversation): ?DelightfulConversationEntity;

    public function getConversationById(string $conversationId): ?DelightfulConversationEntity;

    /**
     * @return DelightfulConversationEntity[]
     */
    public function getConversationByIds(array $conversationIds): array;

    public function addConversation(DelightfulConversationEntity $conversation): DelightfulConversationEntity;

    /**
     * (分organization)getuser与指定user的conversation窗口information.
     * @return array<DelightfulConversationEntity>
     */
    public function getConversationsByReceiveIds(string $userId, array $receiveIds, ?string $userOrganizationCode = null): array;

    public function getReceiveConversationBySenderConversationId(string $senderConversationId): ?DelightfulConversationEntity;

    public function batchAddConversation(array $conversations): bool;

    /**
     * @return DelightfulConversationEntity[]
     */
    public function batchGetConversations(array $userIds, string $receiveId, ConversationType $receiveType): array;

    // 批量移exceptconversation窗口
    public function batchRemoveConversations(array $userIds, string $receiveId, ConversationType $receiveType): int;

    // 批量updateconversation窗口
    public function batchUpdateConversations(array $conversationIds, array $updateData): int;

    public function getAllConversationList(): array;

    public function saveInstructs(string $conversationId, array $instructs): void;

    /**
     * @return DelightfulConversationEntity[]
     */
    public function getRelatedConversationsWithInstructByUserId(array $userIds): array;

    public function batchUpdateInstructs(array $updateData): void;

    public function updateConversationById(string $id, array $data): int;

    public function updateConversationStatusByIds(array $ids, ConversationStatus $status): int;
}
