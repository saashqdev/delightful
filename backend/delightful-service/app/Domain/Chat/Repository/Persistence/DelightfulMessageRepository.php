<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Persistence;

use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulMessageVersionEntity;
use App\Domain\Chat\Repository\Facade\DelightfulMessageRepositoryInterface;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulMessageModel;
use App\Interfaces\Chat\Assembler\MessageAssembler;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\Annotation\CacheEvict;
use Hyperf\Codec\Json;
use Hyperf\DbConnection\Db;

class DelightfulMessageRepository implements DelightfulMessageRepositoryInterface
{
    public function __construct(
        protected DelightfulMessageModel $delightfulMessage
    ) {
    }

    public function createMessage(array $message): void
    {
        $this->delightfulMessage::query()->create($message);
    }

    public function getMessages(array $delightfulMessageIds, ?array $rangMessageTypes = null): array
    {
        // 去除nullvalue
        $delightfulMessageIds = array_filter($delightfulMessageIds);
        if (empty($delightfulMessageIds)) {
            return [];
        }
        $query = $this->delightfulMessage::query()->whereIn('delightful_message_id', $delightfulMessageIds);
        if (! is_null($rangMessageTypes)) {
            $query->whereIn('message_type', $rangMessageTypes);
        }
        return Db::select($query->toSql(), $query->getBindings());
    }

    public function getMessageByDelightfulMessageId(string $delightfulMessageId): ?DelightfulMessageEntity
    {
        $message = $this->getMessageDataByDelightfulMessageId($delightfulMessageId);
        return MessageAssembler::getMessageEntity($message);
    }

    public function deleteByDelightfulMessageIds(array $delightfulMessageIds)
    {
        $delightfulMessageIds = array_values(array_unique(array_filter($delightfulMessageIds)));
        if (empty($delightfulMessageIds)) {
            return;
        }
        $this->delightfulMessage::query()->whereIn('delightful_message_id', $delightfulMessageIds)->delete();
    }

    public function updateMessageContent(string $delightfulMessageId, array $messageContent): void
    {
        $this->delightfulMessage::query()->where('delightful_message_id', $delightfulMessageId)->update(
            [
                'content' => Json::encode($messageContent),
            ]
        );
    }

    #[CacheEvict(prefix: 'getMessageByDelightfulMessageId', value: '_#{messageEntity.delightfulMessageId}')]
    public function updateMessageContentAndVersionId(DelightfulMessageEntity $messageEntity, DelightfulMessageVersionEntity $delightfulMessageVersionEntity): void
    {
        $this->delightfulMessage::query()->where('delightful_message_id', $messageEntity->getDelightfulMessageId())->update(
            [
                'current_version_id' => $delightfulMessageVersionEntity->getVersionId(),
                // editmessageallow修改messagetype
                'message_type' => $messageEntity->getMessageType()->value,
                'content' => Json::encode($messageEntity->getContent()->toArray()),
            ]
        );
    }

    /**
     * Check if message exists by app_message_id and optional message_type.
     * Uses covering index (app_message_id, deleted_at, message_type) to avoid table lookup.
     */
    public function isMessageExistsByAppMessageId(string $appMessageId, string $messageType = ''): bool
    {
        if (empty($appMessageId)) {
            return false;
        }

        // Build query to maximize covering index usage
        // Index order: app_message_id, deleted_at, message_type
        $query = $this->delightfulMessage::query()
            ->select(Db::raw('1'))  // Only select constant to ensure index-only scan
            ->where('app_message_id', $appMessageId)
            ->whereNull('deleted_at');

        // Only add message type filter when messageType is not empty
        if (! empty($messageType)) {
            $query->where('message_type', $messageType);
        }

        // Use limit(1) for early termination since we only care about existence
        return $query->limit(1)->exists();
    }

    public function getDelightfulMessageIdByAppMessageId(string $appMessageId, string $messageType = ''): string
    {
        if (empty($appMessageId)) {
            return '';
        }

        // Build query to maximize covering index usage
        // Index order: app_message_id, deleted_at, message_type
        $query = $this->delightfulMessage::query()
            ->select('delightful_message_id')  // Only select delightful_message_id field
            ->where('app_message_id', $appMessageId)
            ->whereNull('deleted_at');

        // Only add message type filter when messageType is not empty
        if (! empty($messageType)) {
            $query->where('message_type', $messageType);
        }

        // Use limit(1) for early termination and get the first result
        $result = $query->limit(1)->first();

        return $result ? $result->delightful_message_id : '';
    }

    /**
     * Get messages by delightful message IDs.
     * @param array $delightfulMessageIds Delightful message IDarray
     * @return DelightfulMessageEntity[] message实体array
     */
    public function getMessagesByDelightfulMessageIds(array $delightfulMessageIds): array
    {
        if (empty($delightfulMessageIds)) {
            return [];
        }

        $query = $this->delightfulMessage::query()->whereIn('delightful_message_id', $delightfulMessageIds);
        $messages = Db::select($query->toSql(), $query->getBindings());

        return array_map(function ($message) {
            return MessageAssembler::getMessageEntity($message);
        }, $messages);
    }

    /**
     * Batch create messages.
     * @param array $messagesData messagedataarray
     * @return bool 是否createsuccess
     */
    public function batchCreateMessages(array $messagesData): bool
    {
        if (empty($messagesData)) {
            return true;
        }

        return $this->delightfulMessage::query()->insert($messagesData);
    }

    #[Cacheable(prefix: 'getMessageByDelightfulMessageId', value: '_#{delightfulMessageId}', ttl: 10)]
    private function getMessageDataByDelightfulMessageId(string $delightfulMessageId)
    {
        $query = $this->delightfulMessage::query()->where('delightful_message_id', $delightfulMessageId);
        $message = Db::select($query->toSql(), $query->getBindings())[0] ?? null;
        if (empty($message)) {
            return null;
        }
        return $message;
    }
}
