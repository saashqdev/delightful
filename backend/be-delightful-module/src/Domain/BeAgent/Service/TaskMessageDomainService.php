<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Service;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskFileRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskMessageRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TaskMessageModel;
use Hyperf\Contract\StdoutLoggerInterface;
use InvalidArgumentException;

class TaskMessageDomainService 
{
 
    public function __construct( 
    protected TaskMessageRepositoryInterface $messageRepository, 
    protected TaskFileRepositoryInterface $taskFileRepository, 
    private readonly StdoutLoggerInterface $logger ) 
{
 
}
 
    public function getNextSeqId(int $topicId, int $taskId): int 
{
 return $this->messageRepository->getNextSeqId($topicId, $taskId); 
}
 
    public function updateprocess ingStatus(int $id, string $processingStatus, ?string $errorMessage = null, int $retryCount = 0): void 
{
 $this->messageRepository->updateprocess ingStatus($id, $processingStatus, $errorMessage, $retryCount); 
}
 
    public function findprocess ableMessages(int $topicId, int $taskId, string $senderType = 'assistant', int $timeoutMinutes = 30, int $maxRetries = 3, int $limit = 50): array 
{
 return $this->messageRepository->findprocess ableMessages($topicId, $taskId, $senderType, $timeoutMinutes, $maxRetries, $limit); 
}
 
    public function findByTopicIdAndMessageId(int $topicId, string $messageId): ?TaskMessageEntity 
{
 return $this->messageRepository->findByTopicIdAndMessageId($topicId, $messageId); 
}
 
    public function updateExistingMessage(TaskMessageEntity $message): void 
{
 $this->messageRepository->updateExistingMessage($message); 
}
 /** * topic TaskMessage. * * @param TaskMessageEntity $messageEntity Message * @param array $rawData original MessageData * @param string $processStatus process Status * @return TaskMessageEntity Message */ 
    public function storeTopicTaskMessage(TaskMessageEntity $messageEntity, array $rawData, string $processStatus = TaskMessageModel::PROCESSING_STATUS_PENDING): TaskMessageEntity 
{
 $this->logger->info('Start storing topic task message', [ 'topic_id' => $messageEntity->getTopicId(), 'message_id' => $messageEntity->getMessageId(), ]); // 1. Get seq_id (should have been set during DTO conversion) $seqId = $messageEntity->getSeqId(); if ($seqId === null) 
{
 throw new InvalidArgumentException('seq_id must be set before storing message'); 
}
 // 2. check Messagewhether DuplicateThroughseq_id + topic_id $existingMessage = $this->messageRepository->findBySeqIdAndTopicId( $seqId, (int) $messageEntity->getTaskId(), (int) $messageEntity->getTopicId(), ); if ($existingMessage) 
{
 $this->logger->info('Message already exists, skip duplicate storage', [ 'topic_id' => $messageEntity->getTopicId(), 'seq_id' => $seqId, 'task_id' => $messageEntity->getTaskId(), 'message_id' => $messageEntity->getMessageId(), ]); return $existingMessage; 
}
 // 3. Messagedoes not existRow $messageEntity->setRetryCount(0); $this->messageRepository->saveWithRawData( $rawData, // Original data $messageEntity, $processStatus ); $this->logger->info('Topic task message storage completed', [ 'topic_id' => $messageEntity->getTopicId(), 'seq_id' => $seqId, 'message_id' => $messageEntity->getMessageId(), ]); return $messageEntity; 
}
 /** * Update message IM sequence ID. * * @param int $messageId Message ID * @param null|int $imSeqId IM sequence ID, null to skip update */ 
    public function updateMessageSeqId(int $messageId, ?int $imSeqId): void 
{
 $this->messageRepository->updateMessageSeqId($messageId, $imSeqId); 
}
 
}
 
