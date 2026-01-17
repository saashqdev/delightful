<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskMessageModel;

interface TaskMessageRepositoryInterface
{
    /**
     * Get message by ID.
     */
    public function getById(int $id): ?TaskMessageEntity;

    /**
     * Save message.
     */
    public function save(TaskMessageEntity $message): void;

    /**
     * Batch save messages.
     * @param TaskMessageEntity[] $messages
     */
    public function batchSave(array $messages): void;

    /**
     * Get message list by task ID.
     * @return TaskMessageEntity[]
     */
    public function findByTaskId(string $taskId): array;

    /**
     * Get user message list by topic ID and task ID (optimized index + filter user messages).
     * @return TaskMessageEntity[]
     */
    public function findUserMessagesByTopicIdAndTaskId(int $topicId, string $taskId): array;

    /**
     * Get message list by topic ID with pagination support.
     * @param int $topicId Topic ID
     * @param int $page Page number
     * @param int $pageSize Page size
     * @param bool $shouldPage Whether pagination is needed
     * @param string $sortDirection Sort direction, supports asc and desc
     * @param bool $showInUi Whether to only show UI-visible messages
     * @return array Returns array containing message list and total count ['list' => TaskMessageEntity[], 'total' => int]
     */
    public function findByTopicId(int $topicId, int $page = 1, int $pageSize = 20, bool $shouldPage = true, string $sortDirection = 'asc', bool $showInUi = true): array;

    public function getUserFirstMessageByTopicId(int $topicId, string $userId): ?TaskMessageEntity;

    /**
     * Query message list by topic_id and processing status, sorted by seq_id in ascending order.
     * @param int $topicId Topic ID
     * @param string $processingStatus Processing status
     * @param string $senderType Sender type
     * @param int $limit Limit count
     * @return TaskMessageEntity[]
     */
    public function findPendingMessagesByTopicId(int $topicId, string $processingStatus, string $senderType = 'assistant', int $limit = 50): array;

    /**
     * Update message processing status.
     * @param int $id Message ID
     * @param string $processingStatus Processing status
     * @param null|string $errorMessage Error message
     * @param int $retryCount Retry count
     */
    public function updateProcessingStatus(int $id, string $processingStatus, ?string $errorMessage = null, int $retryCount = 0): void;

    /**
     * Batch update message processing status.
     * @param array $ids Message ID array
     * @param string $processingStatus Processing status
     */
    public function batchUpdateProcessingStatus(array $ids, string $processingStatus): void;

    /**
     * Get next seq_id.
     */
    public function getNextSeqId(int $topicId, int $taskId): int;

    /**
     * Save raw message data and generate seq_id.
     * @param array $rawData Raw message data
     * @param TaskMessageEntity $message Message entity
     * @param string $processStatus Processing status
     */
    public function saveWithRawData(array $rawData, TaskMessageEntity $message, string $processStatus = TaskMessageModel::PROCESSING_STATUS_PENDING): void;

    /**
     * Query message by seq_id and topic_id.
     * @param int $seqId Sequence ID
     * @param int $taskId Task ID
     * @param int $topicId Topic ID
     * @return null|TaskMessageEntity Message entity or null
     */
    public function findBySeqIdAndTopicId(int $seqId, int $taskId, int $topicId): ?TaskMessageEntity;

    /**
     * Query message by topic_id and message_id.
     * @param int $topicId Topic ID
     * @param string $messageId Message ID
     * @return null|TaskMessageEntity Message entity or null
     */
    public function findByTopicIdAndMessageId(int $topicId, string $messageId): ?TaskMessageEntity;

    /**
     * Update business fields of existing message.
     * @param TaskMessageEntity $message Message entity
     */
    public function updateExistingMessage(TaskMessageEntity $message): void;

    /**
     * Get list of messages pending processing (for sequential batch processing).
     *
     * Query conditions:
     * - pending: Process all
     * - processing: Those exceeding specified minutes (considered timed out)
     * - failed: Those with retry count not exceeding maximum
     *
     * @param int $topicId Topic ID
     * @param string $senderType Sender type
     * @param int $timeoutMinutes Processing timeout (minutes)
     * @param int $maxRetries Maximum retry count
     * @param int $limit Limit count
     * @return TaskMessageEntity[] Message list sorted by seq_id in ascending order
     */
    public function findProcessableMessages(
        int $topicId,
        int $taskId,
        string $senderType = 'assistant',
        int $timeoutMinutes = 30,
        int $maxRetries = 3,
        int $limit = 50
    ): array;

    /**
     * Get list of messages to copy by topic ID and message ID.
     *
     * @param int $topicId Topic ID
     * @param int $messageId Message ID (get messages less than or equal to this ID)
     * @return TaskMessageEntity[] Message entity array, sorted by id in ascending order
     */
    public function findMessagesToCopyByTopicIdAndMessageId(int $topicId, int $messageId): array;

    /**
     * Batch create messages.
     *
     * @param TaskMessageEntity[] $messageEntities Message entity array
     * @return TaskMessageEntity[] Successfully created message entity array (with generated IDs)
     */
    public function batchCreateMessages(array $messageEntities): array;

    /**
     * Update message's IM sequence ID.
     *
     * @param int $id Message ID
     * @param null|int $imSeqId IM sequence ID, not updated when null
     */
    public function updateMessageSeqId(int $id, ?int $imSeqId): void;
}
