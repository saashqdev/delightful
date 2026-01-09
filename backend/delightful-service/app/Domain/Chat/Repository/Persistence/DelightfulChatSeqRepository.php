<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Persistence;

use App\Domain\Chat\DTO\MessagesQueryDTO;
use App\Domain\Chat\DTO\Response\ClientSequenceResponse;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Repository\Facade\DelightfulChatConversationRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatSeqRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulMessageRepositoryInterface;
use App\Domain\Chat\Repository\Persistence\Model\DelightfulChatSequenceModel;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Repository\Facade\DelightfulAccountRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulUserRepositoryInterface;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Constants\Order;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Chat\Assembler\MessageAssembler;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Codec\Json;
use Hyperf\DbConnection\Db;

class DelightfulChatSeqRepository implements DelightfulChatSeqRepositoryInterface
{
    public function __construct(
        protected DelightfulChatSequenceModel $delightfulSeq,
        protected DelightfulMessageRepositoryInterface $delightfulMessageRepository,
        protected DelightfulAccountRepositoryInterface $delightfulAccountRepository,
        protected DelightfulUserRepositoryInterface $delightfulUserRepository,
        protected DelightfulChatConversationRepositoryInterface $delightfulUserConversationRepository,
    ) {
    }

    public function createSequence(array $message): DelightfulSeqEntity
    {
        if (is_array($message['content'])) {
            $message['content'] = Json::encode($message['content'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if (is_array($message['receive_list'])) {
            $message['receive_list'] = Json::encode($message['receive_list'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $message['extra'] = $this->getSeqExtra($message['extra'] ?? null);
        $this->delightfulSeq::query()->create($message);
        return SeqAssembler::getSeqEntity($message);
    }

    /**
     * @param DelightfulSeqEntity[] $seqList
     * @return DelightfulSeqEntity[]
     */
    public function batchCreateSeq(array $seqList): array
    {
        $insertData = [];
        foreach ($seqList as $seqEntity) {
            // 将entitymiddle的array转为string
            $seqInfo = $seqEntity->toArray();
            $seqInfo['content'] = Json::encode($seqInfo['content']);
            $seqInfo['receive_list'] = Json::encode($seqInfo['receive_list']);
            $seqInfo['extra'] = $this->getSeqExtra($seqInfo['extra'] ?? null);
            // seq 的topic_idactualsavein topic_messages tablemiddle
            unset($seqInfo['topic_id']);
            $insertData[] = $seqInfo;
        }
        $data = $this->delightfulSeq::query()->insert($insertData);
        if (! $data) {
            ExceptionBuilder::throw(ChatErrorCode::DATA_WRITE_FAILED);
        }
        return $seqList;
    }

    /**
     * returnmost大message的倒数 n item序column.
     * message_id= seqtable的primary keyid,thereforenotneed单独对 message_id 加索引.
     * @return ClientSequenceResponse[]
     */
    public function pullRecentMessage(DataIsolation $dataIsolation, int $userLocalMaxSeqId, int $limit): array
    {
        $query = $this->delightfulSeq::query()
            ->where('object_type', $dataIsolation->getUserType())
            ->where('object_id', $dataIsolation->getCurrentDelightfulId());
        if ($userLocalMaxSeqId > 0) {
            $query->where('seq_id', '>', $userLocalMaxSeqId);
        }
        $query->orderBy('seq_id', Order::Desc->value)
            ->limit($limit)
            ->forceIndex('idx_object_type_id_seq_id');
        $seqInfos = Db::select($query->toSql(), $query->getBindings());
        return $this->getClientSequencesResponse($seqInfos);
    }

    /**
     * return $userLocalMaxSeqId 之back的 $limit itemmessage.
     * message_id= seqtable的primary keyid,thereforenotneed单独对 message_id 加索引.
     * @return ClientSequenceResponse[]
     */
    public function getAccountSeqListByDelightfulId(DataIsolation $dataIsolation, int $userLocalMaxSeqId, int $limit): array
    {
        $query = $this->delightfulSeq::query()
            ->where('object_type', $dataIsolation->getUserType())
            ->where('object_id', $dataIsolation->getCurrentDelightfulId())
            ->where('seq_id', '>', $userLocalMaxSeqId)
            ->forceIndex('idx_object_type_id_seq_id')
            ->orderBy('seq_id')
            ->limit($limit);
        $seqInfos = Db::select($query->toSql(), $query->getBindings());
        return $this->getClientSequencesResponse($seqInfos);
    }

    /**
     * according to app_message_id pullmessage.
     * @return ClientSequenceResponse[]
     */
    public function getAccountSeqListByAppMessageId(DataIsolation $dataIsolation, string $appMessageId, string $pageToken, int $pageSize): array
    {
        $query = $this->delightfulSeq::query()
            ->where('object_type', $dataIsolation->getUserType())
            ->where('object_id', $dataIsolation->getCurrentDelightfulId())
            ->where('app_message_id', $appMessageId)
            ->when(! empty($pageToken), function ($query) use ($pageToken) {
                $query->where('seq_id', '>', $pageToken);
            })
            ->orderBy('seq_id')
            ->limit($pageSize);
        $seqInfos = Db::select($query->toSql(), $query->getBindings());
        return $this->getClientSequencesResponse($seqInfos);
    }

    public function getSeqByMessageId(string $messageId): ?DelightfulSeqEntity
    {
        $seqInfo = $this->getSeq($messageId);
        if ($seqInfo === null) {
            return null;
        }
        return SeqAssembler::getSeqEntity($seqInfo);
    }

    /**
     * @return ClientSequenceResponse[]
     * @todo 挪to delightful_chat_topic_messages process
     * session窗口scrollloadhistoryrecord.
     * message_id= seqtable的primary keyid,thereforenotneed单独对 message_id 加索引.
     */
    public function getConversationChatMessages(MessagesQueryDTO $messagesQueryDTO): array
    {
        return $this->getConversationsChatMessages($messagesQueryDTO, [$messagesQueryDTO->getConversationId()]);
    }

    /**
     * @return ClientSequenceResponse[]
     * @todo 挪to delightful_chat_topic_messages process
     * session窗口scrollloadhistoryrecord.
     * message_id= seqtable的primary keyid,thereforenotneed单独对 message_id 加索引.
     */
    public function getConversationsChatMessages(MessagesQueryDTO $messagesQueryDTO, array $conversationIds): array
    {
        $order = $messagesQueryDTO->getOrder();
        if ($order === Order::Desc) {
            $operator = '<';
            $direction = 'desc';
        } else {
            $operator = '>';
            $direction = 'asc';
        }
        $timeStart = $messagesQueryDTO->getTimeStart();
        $timeEnd = $messagesQueryDTO->getTimeEnd();
        $pageToken = $messagesQueryDTO->getPageToken();
        $limit = $messagesQueryDTO->getLimit();
        $query = $this->delightfulSeq::query()->whereIn('conversation_id', $conversationIds);
        if (! empty($pageToken)) {
            // currentsessionhistorymessagemiddlemost小的 seq id. willuse来查ratio它also小的value
            $query->where('seq_id', $operator, $pageToken);
        }
        if ($timeStart !== null) {
            $query->where('created_at', '>=', $timeStart->toDateTimeString());
        }
        if ($timeEnd !== null) {
            $query->where('created_at', '<=', $timeEnd->toDateTimeString());
        }
        $query->orderBy('seq_id', $direction)->limit($limit);
        $seqList = Db::select($query->toSql(), $query->getBindings());
        return $this->getMessagesBySeqList($seqList, $order);
    }

    /**
     * minutegroupgetsessiondownmostnew几itemmessage.
     */
    public function getConversationsMessagesGroupById(MessagesQueryDTO $messagesQueryDTO, array $conversationIds): array
    {
        $rawSql = <<<'sql'
        WITH RankedMessages AS (
            SELECT
                *,
                ROW_NUMBER() OVER(PARTITION BY conversation_id ORDER BY seq_id DESC) as row_num
            FROM
                delightful_chat_sequences
            WHERE
                conversation_id IN (%s)
        )
        SELECT * FROM RankedMessages WHERE row_num <= ? ORDER BY conversation_id, seq_id DESC
sql;
        // generatepdobind
        $pdoBinds = implode(',', array_fill(0, count($conversationIds), '?'));
        $query = sprintf($rawSql, $pdoBinds);
        $seqList = Db::select($query, [...$conversationIds, $messagesQueryDTO->getLimit()]);
        return $this->getMessagesBySeqList($seqList);
    }

    /**
     * get收item方message的status变morestream.
     * message_id= seqtable的primary keyid,thereforenotneed单独对 message_id 加索引.
     * @return DelightfulSeqEntity[]
     */
    public function getReceiveMessagesStatusChange(array $referMessageIds, string $userId): array
    {
        $userEntity = $this->getAccountIdByUserId($userId);
        if ($userEntity === null) {
            return [];
        }
        return $this->getMessagesStatusChangeSeq($referMessageIds, $userEntity);
    }

    /**
     * gethairitem方message的status变morestream.
     * message_id= seqtable的primary keyid,thereforenotneed单独对 message_id 加索引.
     * @return DelightfulSeqEntity[]
     */
    public function getSenderMessagesStatusChange(string $senderMessageId, string $userId): array
    {
        $userEntity = $this->getAccountIdByUserId($userId);
        if ($userEntity === null) {
            return [];
        }
        return $this->getMessagesStatusChangeSeq([$senderMessageId], $userEntity);
    }

    /**
     * @return ClientSequenceResponse[]
     */
    public function getConversationMessagesBySeqIds(array $messageIds, Order $order): array
    {
        $query = $this->delightfulSeq::query()
            ->whereIn('id', $messageIds)
            ->orderBy('id', $order->value);
        $seqList = Db::select($query->toSql(), $query->getBindings());
        return $this->getMessagesBySeqList($seqList, $order);
    }

    /**
     * message_id= seqtable的primary keyid,thereforenotneed单独对 message_id 加索引.
     */
    public function getMessageReceiveList(string $messageId, string $delightfulId, ConversationType $userType): ?array
    {
        // messagestatushair生了变more
        $statusChangeSeq = $this->delightfulSeq::query()
            ->where('object_id', $delightfulId)
            ->where('object_type', $userType->value)
            ->where('refer_message_id', $messageId)
            ->whereIn('seq_type', ControlMessageType::getMessageStatusChangeType())
            ->forceIndex('idx_object_type_id_refer_message_id')
            ->orderBy('seq_id', 'desc');
        $statusChangeSeq = Db::select($statusChangeSeq->toSql(), $statusChangeSeq->getBindings())[0] ?? null;
        if (empty($statusChangeSeq)) {
            // nothavestatus变more的message
            $statusChangeSeq = $this->delightfulSeq::query()
                ->where('id', $messageId)
                ->orderBy('id', 'desc');
            $statusChangeSeq = Db::select($statusChangeSeq->toSql(), $statusChangeSeq->getBindings())[0] ?? null;
        }
        return $statusChangeSeq;
    }

    /**
     * Retrieve the sequence (seq) lists of both the sender and the receiver based on the $delightfulMessageId (generally used in the message editing scenario).
     */
    public function getBothSeqListByDelightfulMessageId(string $delightfulMessageId): array
    {
        $query = $this->delightfulSeq::query()->where('delightful_message_id', $delightfulMessageId);
        return Db::select($query->toSql(), $query->getBindings());
    }

    /**
     * Optimized version: Group by object_id at MySQL level and return only the minimum seq_id record for each user
     * Supports message editing functionality and reduces data transfer volume.
     *
     * Performance optimization recommendations:
     * 1. Add composite index: CREATE INDEX idx_delightful_message_id_object_id_seq_id ON delightful_chat_sequences (delightful_message_id, object_id, seq_id)
     * 2. This avoids table lookup queries and completes operations directly on the index
     */
    public function getMinSeqListByDelightfulMessageId(string $delightfulMessageId): array
    {
        // Use window function to group by object_id and select only the minimum seq_id for each user
        $sql = '
            SELECT * FROM (
                SELECT *,
                       ROW_NUMBER() OVER (PARTITION BY object_id ORDER BY seq_id ASC) as rn
                FROM delightful_chat_sequences 
                WHERE delightful_message_id = ?
            ) t 
            WHERE t.rn = 1
        ';

        return Db::select($sql, [$delightfulMessageId]);
    }

    /**
     * getmessage的withdraw seq.
     */
    public function getMessageRevokedSeq(string $messageId, DelightfulUserEntity $userEntity, ControlMessageType $controlMessageType): ?DelightfulSeqEntity
    {
        $accountId = $userEntity->getDelightfulId();
        $query = $this->delightfulSeq::query()
            ->where('object_type', $userEntity->getUserType()->value)
            ->where('object_id', $accountId)
            ->where('refer_message_id', $messageId)
            ->where('seq_type', $controlMessageType->value)
            ->forceIndex('idx_object_type_id_refer_message_id');
        $seqInfo = Db::select($query->toSql(), $query->getBindings())[0] ?? null;
        if ($seqInfo === null) {
            return null;
        }
        return SeqAssembler::getSeqEntity($seqInfo);
    }

    // todo 移to delightful_chat_topic_messages process
    public function getConversationSeqByType(string $delightfulId, string $conversationId, ControlMessageType $seqType): ?DelightfulSeqEntity
    {
        $query = $this->delightfulSeq::query()
            ->where('conversation_id', $conversationId)
            ->where('seq_type', $seqType->value)
            ->where('object_id', $delightfulId)
            ->where('object_type', ConversationType::User->value)
            ->forceIndex('idx_conversation_id_seq_type');
        $seqInfo = Db::select($query->toSql(), $query->getBindings())[0] ?? null;
        if ($seqInfo === null) {
            return null;
        }
        return SeqAssembler::getSeqEntity($seqInfo);
    }

    /**
     * @return DelightfulSeqEntity[]
     */
    public function batchGetSeqByMessageIds(array $messageIds): array
    {
        $query = $this->delightfulSeq::query()->whereIn('id', $messageIds);
        $seqList = Db::select($query->toSql(), $query->getBindings());
        return $this->getSeqEntities($seqList);
    }

    public function updateSeqExtra(string $seqId, SeqExtra $seqExtra): bool
    {
        return (bool) $this->delightfulSeq::query()
            ->where('id', $seqId)
            ->update(['extra' => Json::encode($seqExtra->toArray())]);
    }

    public function getSeqMessageByIds(array $ids): array
    {
        $query = $this->delightfulSeq::query()->whereIn('id', $ids);
        return Db::select($query->toSql(), $query->getBindings());
    }

    public function deleteSeqMessageByIds(array $seqIds): int
    {
        $seqIds = array_values(array_filter(array_unique($seqIds)));
        if (empty($seqIds)) {
            return 0;
        }
        return (int) $this->delightfulSeq::query()->whereIn('id', $seqIds)->delete();
    }

    // 为了移except脏data写的method
    public function getSeqByDelightfulId(string $delightfulId, int $limit): array
    {
        $query = $this->delightfulSeq::query()
            ->where('object_type', ConversationType::User->value)
            ->where('object_id', $delightfulId)
            ->limit($limit);
        return Db::select($query->toSql(), $query->getBindings());
    }

    // 为了移except脏data写的method
    public function getHasTrashMessageUsers(): array
    {
        // 按 delightful_id minutegroup,找出have垃圾message的user
        $query = $this->delightfulSeq::query()
            ->select('object_id')
            ->groupBy('object_id')
            ->havingRaw('count(*) < 100');
        return Db::select($query->toSql(), $query->getBindings());
    }

    public function batchUpdateSeqStatus(array $seqIds, DelightfulMessageStatus $status): int
    {
        $seqIds = array_values(array_unique($seqIds));
        if (empty($seqIds)) {
            return 0;
        }
        return $this->delightfulSeq::query()
            ->whereIn('id', $seqIds)
            ->update(['status' => $status->value]);
    }

    public function updateSeqRelation(DelightfulSeqEntity $seqEntity): bool
    {
        return (bool) $this->delightfulSeq::query()
            ->where('id', $seqEntity->getId())
            ->update(
                [
                    'extra' => Json::encode($seqEntity->getExtra()?->toArray()),
                ]
            );
    }

    /**
     * updatemessagereceivepersonlist.
     */
    public function updateReceiveList(DelightfulSeqEntity $seqEntity): bool
    {
        $receiveList = $seqEntity->getReceiveList();
        $receiveListJson = $receiveList ? Json::encode($receiveList->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;

        return (bool) $this->delightfulSeq::query()
            ->where('id', $seqEntity->getId())
            ->update([
                'receive_list' => $receiveListJson,
            ]);
    }

    /**
     * Get sequences by conversation ID and seq IDs.
     * @param string $conversationId sessionID
     * @param array $seqIds 序columnIDarray
     * @return DelightfulSeqEntity[] 序column实bodyarray
     */
    public function getSequencesByConversationIdAndSeqIds(string $conversationId, array $seqIds): array
    {
        if (empty($seqIds)) {
            return [];
        }

        $query = $this->delightfulSeq::query()
            ->where('conversation_id', $conversationId)
            ->whereIn('id', $seqIds)
            ->orderBy('id', 'asc');

        $seqList = Db::select($query->toSql(), $query->getBindings());
        return $this->getSeqEntities($seqList);
    }

    /**
     * getmessage的status变morestream.
     * @return DelightfulSeqEntity[]
     */
    private function getMessagesStatusChangeSeq(array $referMessageIds, DelightfulUserEntity $userEntity): array
    {
        // 将 orWhereIn 拆minute为 2 itemquery,避免索引失效
        $query = $this->delightfulSeq::query()
            ->where('object_type', $userEntity->getUserType()->value)
            ->where('object_id', $userEntity->getDelightfulId())
            ->whereIn('refer_message_id', $referMessageIds)
            ->forceIndex('idx_object_type_id_refer_message_id')
            ->orderBy('seq_id', 'desc');
        $referMessages = Db::select($query->toSql(), $query->getBindings());
        // from refer_message_id middle找出message的most新status
        $query = $this->delightfulSeq::query()
            ->where('object_type', $userEntity->getUserType()->value)
            ->where('object_id', $userEntity->getDelightfulId())
            ->whereIn('seq_id', $referMessageIds)
            ->forceIndex('idx_object_type_id_seq_id')
            ->orderBy('seq_id', 'desc');
        $seqList = Db::select($query->toSql(), $query->getBindings());
        // mergebackagain降序rowcolumn,快speed找出message的most新status
        $seqList = array_merge($seqList, $referMessages);
        $seqList = array_column($seqList, null, 'id');
        krsort($seqList);
        return $this->getSeqEntities($seqList);
    }

    /**
     * 对result集force重新降序rowcolumn.
     * @return ClientSequenceResponse[]
     */
    private function getClientSequencesResponse(array $seqInfos): array
    {
        $delightfulMessageIds = [];
        // chatmessage,查messagetablegetmessagecontent
        foreach ($seqInfos as $seqInfo) {
            $seqType = MessageAssembler::getMessageType($seqInfo['seq_type']);
            if ($seqType instanceof ChatMessageType) {
                $delightfulMessageIds[] = $seqInfo['delightful_message_id'];
            }
        }
        $messages = [];
        if (! empty($delightfulMessageIds)) {
            $messages = $this->delightfulMessageRepository->getMessages($delightfulMessageIds);
        }
        // 将控制message/chatmessage一起放入user的messagestreammiddle
        return SeqAssembler::getClientSeqStructs($seqInfos, $messages);
    }

    private function getSeqExtra(null|array|string $extra): string
    {
        if (empty($extra)) {
            return '{}';
        }
        return is_array($extra)
            ? Json::encode($extra, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : $extra;
    }

    #[Cacheable(prefix: 'getSeqEntity', ttl: 60)]
    private function getSeq(string $messageId): ?array
    {
        $query = $this->delightfulSeq::query()->where('id', $messageId);
        return Db::select($query->toSql(), $query->getBindings())[0] ?? null;
    }

    /**
     * 批quantityreturncustomer端need的Seq结构.
     * @return ClientSequenceResponse[]
     */
    private function getMessagesBySeqList(array $seqList, Order $order = Order::Desc): array
    {
        // fromMessagestablegetmessagecontent
        $delightfulMessageIds = array_column($seqList, 'delightful_message_id');
        $messages = $this->delightfulMessageRepository->getMessages($delightfulMessageIds);
        $clientSequenceResponses = SeqAssembler::getClientSeqStructs($seqList, $messages);
        return SeqAssembler::sortSeqList($clientSequenceResponses, $order);
    }

    // 避免 redis cacheserialize的object,占usetoo多inside存
    private function getAccountIdByUserId(string $uid): ?DelightfulUserEntity
    {
        // according touid找toaccount_id
        return $this->delightfulUserRepository->getUserById($uid);
    }

    /**
     * @return DelightfulSeqEntity[]
     */
    private function getSeqEntities(array $seqList): array
    {
        if (empty($seqList)) {
            return [];
        }
        $seqEntities = [];
        foreach ($seqList as $seqInfo) {
            $seqEntities[] = SeqAssembler::getSeqEntity($seqInfo);
        }
        return $seqEntities;
    }
}
