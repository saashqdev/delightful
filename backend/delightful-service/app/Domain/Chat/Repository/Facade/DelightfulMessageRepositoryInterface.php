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

    public function getMessages(array $delightfulMessageIds, ?array $rangMessageTypes = null): array;

    public function getMessageByDelightfulMessageId(string $delightfulMessageId): ?DelightfulMessageEntity;

    public function deleteByDelightfulMessageIds(array $delightfulMessageIds);

    public function updateMessageContent(string $delightfulMessageId, array $messageContent): void;

    public function updateMessageContentAndVersionId(DelightfulMessageEntity $messageEntity, DelightfulMessageVersionEntity $delightfulMessageVersionEntity): void;

    /**
     * Check if message exists by app_message_id and optional message_type.
     */
    public function isMessageExistsByAppMessageId(string $appMessageId, string $messageType = ''): bool;

    public function getDelightfulMessageIdByAppMessageId(string $appMessageId, string $messageType = ''): string;

    /**
     * Get messages by delightful message IDs.
     * @param array $delightfulMessageIds Delightful message IDarray
     * @return DelightfulMessageEntity[] message实体array
     */
    public function getMessagesByDelightfulMessageIds(array $delightfulMessageIds): array;

    /**
     * Batch create messages.
     * @param array $messagesData messagedataarray
     * @return bool whethercreatesuccess
     */
    public function batchCreateMessages(array $messagesData): bool;
}
