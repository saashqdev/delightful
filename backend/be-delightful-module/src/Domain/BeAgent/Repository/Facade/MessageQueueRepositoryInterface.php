<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\MessageQueueEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\MessageQueueStatus;

interface MessageQueueRepositoryInterface 
{
 /** * Create message queue. */ 
    public function create(MessageQueueEntity $messageQueue): MessageQueueEntity; /** * Update message queue. */ 
    public function update(MessageQueueEntity $messageQueue): bool; /** * delete message queue (soft delete). */ 
    public function delete(int $id, string $userId): bool; /** * Get pending messages by topic ID. * * @param int $topicId Topic ID * @param string $userId user ID * @return MessageQueueEntity[] */ 
    public function getPendingMessagesByTopic(int $topicId, string $userId): array; /** * Get message queue by ID. */ 
    public function getById(int $id): ?MessageQueueEntity; /** * Get message queue by ID for specific user. */ 
    public function getByIdForuser (int $id, string $userId): ?MessageQueueEntity; /** * Update message status. */ 
    public function updateStatus(int $id, MessageQueueStatus $status, ?string $errorMessage = null): bool; /** * Get messages with status filter. * * @param array $conditions query conditions * @param MessageQueueStatus[] $statuses Status array to filter * @param bool $needPagination whether to use pagination * @param int $pageSize Page size * @param int $page Page number * @return array
{
list: MessageQueueEntity[], total: int
}
 */ 
    public function getMessagesByStatuses( array $conditions = [], array $statuses = [], bool $needPagination = true, int $pageSize = 10, int $page = 1, string $orderBy = 'id', string $order = 'asc' ): array;
/** * Get next pending message for consumption. */ 
    public function getNextPendingMessage(string $userId, ?int $topicId = null): ?MessageQueueEntity; /** * Update message with conditions (for status changes with concurrency control). */ 
    public function updateWithConditions(int $id, array $data, array $conditions = []): bool; /** * Get topic IDs that have pending messages for compensation. * GetHavePendingMessagetopic IDlist for compensation process . * * @param int $limit Maximum number of topics to return * @param array $organizationCodes Organization codes filter (empty array means all organizations) * @return array Array of topic IDs */ 
    public function getCompensationTopics(int $limit, array $organizationCodes = []): array; /** * Get earliest pending message for specific topic. * Getspecified topic PendingMessage. * * @param int $topicId Topic ID * @param null|string $maxexecute Time Max execute time filter (optional, if null then no time filter applied) * @return null|MessageQueueEntity Earliest pending message or null if none found */ 
    public function getEarliestMessageByTopic(int $topicId, ?string $maxexecute Time = null): ?MessageQueueEntity; /** * Delay execution time for all pending messages in a topic. * Delayedtopic AllPendingMessageexecute Time. * * @param int $topicId Topic ID * @param int $delayMinutes Delay time in minutes * @return bool True if any messages were updated, false otherwise */ 
    public function delayTopicMessages(int $topicId, int $delayMinutes): bool; 
}
 
