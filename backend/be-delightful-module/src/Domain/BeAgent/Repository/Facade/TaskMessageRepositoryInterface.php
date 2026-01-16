<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskMessageEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TaskMessageModel;

interface TaskMessageRepositoryInterface 
{
 /** * ThroughIDGetMessage. */ 
    public function getById(int $id): ?TaskMessageEntity; /** * SaveMessage. */ 
    public function save(TaskMessageEntity $message): void; /** * BatchSaveMessage. * @param TaskMessageEntity[] $messages */ 
    public function batchSave(array $messages): void; /** * According toTaskIDGetMessagelist . * @return TaskMessageEntity[] */ 
    public function findByTaskId(string $taskId): array; /** * According totopic IDTaskIDGetuser Messagelist optimize Index+Filteruser Message. * @return TaskMessageEntity[] */ 
    public function finduser MessagesByTopicIdAndTaskId(int $topicId, string $taskId): array; /** * According totopic IDGetMessagelist SupportPaging. * @param int $topicId topic ID * @param int $page Page number * @param int $pageSize Per pageSize * @param bool $shouldPage whether need Paging * @param string $sortDirection SortSupportascdesc * @param bool $showInUi whether DisplayUIVisibleMessage * @return array Return including Messagelist TotalArray ['list' => TaskMessageEntity[], 'total' => int] */ 
    public function findByTopicId(int $topicId, int $page = 1, int $pageSize = 20, bool $shouldPage = true, string $sortDirection = 'asc', bool $showInUi = true): array; 
    public function getuser FirstMessageByTopicId(int $topicId, string $userId): ?TaskMessageEntity; /** * According totopic_idprocess Statusquery Messagelist seq_idAscendingColumn. * @param int $topicId topic ID * @param string $processingStatus process Status * @param string $senderType SendType * @param int $limit LimitQuantity * @return TaskMessageEntity[] */ 
    public function findPendingMessagesByTopicId(int $topicId, string $processingStatus, string $senderType = 'assistant', int $limit = 50): array; /** * UpdateMessageprocess Status. * @param int $id MessageID * @param string $processingStatus process Status * @param null|string $errorMessage Error message * @param int $retryCount Retry */ 
    public function updateprocess ingStatus(int $id, string $processingStatus, ?string $errorMessage = null, int $retryCount = 0): void; /** * BatchUpdateMessageprocess Status. * @param array $ids MessageIDArray * @param string $processingStatus process Status */ 
    public function batchUpdateprocess ingStatus(array $ids, string $processingStatus): void; /** * GetNextseq_id. */ 
    public function getNextSeqId(int $topicId, int $taskId): int; /** * Saveoriginal MessageDataGenerate seq_id. * @param array $rawData original MessageData * @param TaskMessageEntity $message Message * @param string $processStatus process Status */ 
    public function saveWithRawData(array $rawData, TaskMessageEntity $message, string $processStatus = TaskMessageModel::PROCESSING_STATUS_PENDING): void; /** * According toseq_idtopic_idquery Message. * @param int $seqId ColumnID * @param int $taskId TaskID * @param int $topicId topic ID * @return null|TaskMessageEntity Messageor null */ 
    public function findBySeqIdAndTopicId(int $seqId, int $taskId, int $topicId): ?TaskMessageEntity; /** * According totopic_idmessage_idquery Message. * @param int $topicId topic ID * @param string $messageId MessageID * @return null|TaskMessageEntity Messageor null */ 
    public function findByTopicIdAndMessageId(int $topicId, string $messageId): ?TaskMessageEntity; /** * UpdateHaveMessageField. * @param TaskMessageEntity $message Message */ 
    public function updateExistingMessage(TaskMessageEntity $message): void; /** * GetPendingMessagelist for order Batchprocess . * * query Condition * - pending: Allprocess * - processing: specified as Timed out * - failed: RetryMaximumValue * * @param int $topicId topic ID * @param string $senderType SendType * @param int $timeoutMinutes process TimeoutTime * @param int $maxRetries MaximumRetry * @param int $limit LimitQuantity * @return TaskMessageEntity[] seq_idAscendingColumnMessagelist */ 
    public function findprocess ableMessages( int $topicId, int $taskId, string $senderType = 'assistant', int $timeoutMinutes = 30, int $maxRetries = 3, int $limit = 50 ): array; /** * According totopic IDMessageIDGetneed CopyMessagelist . * * @param int $topicId topic ID * @param int $messageId MessageIDGetLess than or equal toIDMessage * @return TaskMessageEntity[] MessageArrayidAscendingColumn */ 
    public function findMessagesToCopyByTopicIdAndMessageId(int $topicId, int $messageId): array; /** * BatchCreateMessage. * * @param TaskMessageEntity[] $messageEntities MessageArray * @return TaskMessageEntity[] CreateSuccessMessageArrayincluding Generate ID */ 
    public function batchCreateMessages(array $messageEntities): array; /** * UpdateMessageIMColumnID. * * @param int $id MessageID * @param null|int $imSeqId IMColumnIDEmptyUpdate */ 
    public function updateMessageSeqId(int $id, ?int $imSeqId): void; 
}
 
