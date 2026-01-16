<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade;

use App\Domain\Chat\Entity\ValueObject\MagicMessageStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;

interface TopicRepositoryInterface 
{
 /** * ThroughIDGettopic . */ 
    public function getTopicById(int $id): ?TopicEntity; /** * BatchGettopic . * @return TopicEntity[] */ 
    public function getTopicsByIds(array $ids): array; 
    public function getTopicWithdelete d(int $id): ?TopicEntity; 
    public function getTopicBySandboxId(string $sandboxId): ?TopicEntity; /** * According toConditionGettopic list . * SupportFilterPagingSort. * * @param array $conditions query Condition ['workspace_id' => 1, 'user_id' => 'xxx'] * @param bool $needPagination whether need Paging * @param int $pageSize PagingSize * @param int $page Page number * @param string $orderBy SortField * @param string $orderDirection Sortasc or desc * @return array
{
list: TopicEntity[], total: int
}
 topic list Total */ 
    public function getTopicsByConditions( array $conditions = [], bool $needPagination = true, int $pageSize = 10, int $page = 1, string $orderBy = 'id', string $orderDirection = 'desc' ): array; /** * Createtopic . */ 
    public function createTopic(TopicEntity $topicEntity): TopicEntity; /** * Updatetopic . */ 
    public function updateTopic(TopicEntity $topicEntity): bool; /** * Usingupdated_at as LockUpdatetopic . */ 
    public function updateTopicWithUpdatedAt(TopicEntity $topicEntity, string $updatedAt): bool; 
    public function updateTopicByCondition(array $condition, array $data): bool; /** * delete topic . */ 
    public function deleteTopic(int $id): bool; /** * Throughtopic IDCollectionGetworkspace info . * * @param array $topicIds topic IDCollection * @return array topic IDas Keyworkspace info as ValueAssociationArray */ 
    public function getWorkspaceinfo ByTopicIds(array $topicIds): array; /** * Gettopic StatusCountData. * * @param array $conditions CountCondition ['user_id' => '123', 'organization_code' => 'abc'] * @return array including StatusQuantityArray */ 
    public function getTopicStatusMetrics(array $conditions = []): array; 
    public function updateTopicStatus(int $id, $taskId, TaskStatus $status): bool; 
    public function updateTopicStatusAndSandboxId(int $id, $taskId, TaskStatus $status, string $sandboxId): bool; /** * Getmost recently Update timespecified Timetopic list . * * @param string $timeThreshold TimeThresholdIftopic Update timeTimeincluding AtResultin * @param int $limit Return ResultMaximumQuantity * @return array<TopicEntity> topic list */ 
    public function getTopicsExceedingUpdateTime(string $timeThreshold, int $limit = 100): array; /** * According toProject IDGettopic list . */ 
    public function getTopicsByProjectId(int $projectId, string $userId): array; 
    public function updateTopicStatusBySandboxIds(array $sandboxIds, string $status); /** * CountItemunder topic Quantity. */ 
    public function countTopicsByProjectId(int $projectId): int; 
    public function getRunningWorkspaceIds(array $workspaceIds, ?string $userId = null): array; 
    public function getRunningProjectIds(array $projectIds, ?string $userId = null): array; // ======================= MessageRollbackrelated Method ======================= /** * According toColumnIDGetmagic_message_id. */ 
    public function getMagicMessageIdBySeqId(string $seqId): ?string; /** * According tomagic_message_idGetAllrelated seq_idAll. */ 
    public function getAllSeqIdsByMagicMessageId(string $magicMessageId): array; /** * According toBaseseq_idsGetcurrent topic current MessageMessageAllMessage. * @param array $baseSeqIds Baseseq_ids * @return array Allrelated seq_ids */ 
    public function getAllSeqIdsFromcurrent (array $baseSeqIds): array; /** * delete topic_messagesData. */ 
    public function deleteTopicMessages(array $seqIds): int; /** * According toseq_idsdelete messagessequencesData. */ 
    public function deleteMessagesAndSequencesBySeqIds(array $seqIds): bool; /** * According toim_seq_iddelete magic_super_agent_messagetable in Pairtopic Message. * * delete  * 1. query magic_super_agent_message table by im_seq_id, get corresponding primary key id and topic_id * 2. delete all data in current topic where id >= queried primary key id * * @param int $seqId IMMessageColumnID * @return int delete record */ 
    public function deleteSuperAgentMessagesFromSeqId(int $seqId): int; /** * BatchUpdatemagic_chat_sequencestable statusField. * * @param array $seqIds need UpdateColumnIDArray * @param MagicMessageStatus $status TargetStatus * @return bool Updatewhether Success */ 
    public function batchUpdateSeqStatus(array $seqIds, MagicMessageStatus $status): bool; /** * According toBaseseq_idsGetcurrent topic in Less thanspecified seq_idAllMessage. * * @param array $baseSeqIds Baseseq_ids * @return array Less thanspecified seq_idAllMessagelist */ 
    public function getAllSeqIdsBeforecurrent (array $baseSeqIds): array; /** * According totopic IDGetAllrecalled status Messageseq_ids. * * @param int $topicId topic ID * @param string $userId user IDpermission Validate  * @return array recalled status Messageseq_ids */ 
    public function getRevokedSeqIdsByTopicId(int $topicId, string $userId): array; /** * Batch get topic names by IDs. * * @param array $topicIds Topic ID array * @return array ['topic_id' => 'topic_name'] key-value pairs */ 
    public function getTopicNamesBatch(array $topicIds): array; 
}
 
