<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Facade;

use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulMessageVersionEntity;

interface DelightfulMessageRepositoryInterface
{
    public function createMessage(array $message): void;

    public function getMessages(array $magicMessageIds, ?array $rangMessageTypes = null): array;

    public function getMessageByDelightfulMessageId(string $magicMessageId): ?DelightfulMessageEntity;

    public function deleteByDelightfulMessageIds(array $magicMessageIds);

    public function updateMessageContent(string $magicMessageId, array $messageContent): void;

    public function updateMessageContentAndVersionId(DelightfulMessageEntity $messageEntity, DelightfulMessageVersionEntity $magicMessageVersionEntity): void;

    /**
     * Check if message exists by app_message_id and optional message_type.
     */
    public function isMessageExistsByAppMessageId(string $appMessageId, string $messageType = ''): bool;

    public function getDelightfulMessageIdByAppMessageId(string $appMessageId, string $messageType = ''): string;

    /**
     * Get messages by magic message IDs.
     * @param array $magicMessageIds Delightful message ID数组
     * @return DelightfulMessageEntity[] 消息实体数组
     */
    public function getMessagesByDelightfulMessageIds(array $magicMessageIds): array;

    /**
     * Batch create messages.
     * @param array $messagesData 消息数据数组
     * @return bool 是否创建成功
     */
    public function batchCreateMessages(array $messagesData): bool;
}
