<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Service;

use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskFileRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskMessageRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TaskMessageModel;
use Hyperf\Contract\StdoutLoggerInterface;
use InvalidArgumentException;

class TaskMessageDomainService
{
    public function __construct(
        protected TaskMessageRepositoryInterface $messageRepository,
        protected TaskFileRepositoryInterface $taskFileRepository,
        private readonly StdoutLoggerInterface $logger
    ) {
    }

    public function getNextSeqId(int $topicId, int $taskId): int
    {
        return $this->messageRepository->getNextSeqId($topicId, $taskId);
    }

    public function updateProcessingStatus(int $id, string $processingStatus, ?string $errorMessage = null, int $retryCount = 0): void
    {
        $this->messageRepository->updateProcessingStatus($id, $processingStatus, $errorMessage, $retryCount);
    }

    public function findProcessableMessages(int $topicId, int $taskId, string $senderType = 'assistant', int $timeoutMinutes = 30, int $maxRetries = 3, int $limit = 50): array
    {
        return $this->messageRepository->findProcessableMessages($topicId, $taskId, $senderType, $timeoutMinutes, $maxRetries, $limit);
    }

    public function findByTopicIdAndMessageId(int $topicId, string $messageId): ?TaskMessageEntity
    {
        return $this->messageRepository->findByTopicIdAndMessageId($topicId, $messageId);
    }

    public function updateExistingMessage(TaskMessageEntity $message): void
    {
        $this->messageRepository->updateExistingMessage($message);
    }

    /**
     * Store topic task message.
     *
     * @param TaskMessageEntity $messageEntity Message entity
     * @param array $rawData Raw message data
     * @param string $processStatus Processing status
     * @return TaskMessageEntity Stored message entity
     */
    public function storeTopicTaskMessage(TaskMessageEntity $messageEntity, array $rawData, string $processStatus = TaskMessageModel::PROCESSING_STATUS_PENDING): TaskMessageEntity
    {
        $this->logger->info('Start storing topic task message', [
            'topic_id' => $messageEntity->getTopicId(),
            'message_id' => $messageEntity->getMessageId(),
        ]);

        // 1. Get seq_id (should already be set during DTO conversion)
        $seqId = $messageEntity->getSeqId();
        if ($seqId === null) {
            throw new InvalidArgumentException('seq_id must be set before storing message');
        }

        // 2. Check if message is duplicate (by seq_id + topic_id)
        $existingMessage = $this->messageRepository->findBySeqIdAndTopicId(
            $seqId,
            (int) $messageEntity->getTaskId(),
            (int) $messageEntity->getTopicId(),
        );

        if ($existingMessage) {
            $this->logger->info('Message already exists, skip duplicate storage', [
                'topic_id' => $messageEntity->getTopicId(),
                'seq_id' => $seqId,
                'task_id' => $messageEntity->getTaskId(),
                'message_id' => $messageEntity->getMessageId(),
            ]);
            return $existingMessage;
        }

        // 3. Message does not exist, proceed to store
        $messageEntity->setRetryCount(0);
        $this->messageRepository->saveWithRawData(
            $rawData, // Raw data
            $messageEntity,
            $processStatus
        );

        $this->logger->info('Topic task message storage completed', [
            'topic_id' => $messageEntity->getTopicId(),
            'seq_id' => $seqId,
            'message_id' => $messageEntity->getMessageId(),
        ]);

        return $messageEntity;
    }

    /**
     * Update message IM sequence ID.
     *
     * @param int $messageId Message ID
     * @param null|int $imSeqId IM sequence ID, null to skip update
     */
    public function updateMessageSeqId(int $messageId, ?int $imSeqId): void
    {
        $this->messageRepository->updateMessageSeqId($messageId, $imSeqId);
    }
}
