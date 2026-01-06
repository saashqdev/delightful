<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Facade;

use App\Domain\Chat\DTO\MessagesQueryDTO;
use App\Domain\Chat\DTO\Response\ClientSequenceResponse;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Infrastructure\Core\Constants\Order;

interface DelightfulChatSeqRepositoryInterface
{
    public function createSequence(array $message): DelightfulSeqEntity;

    /**
     * @param DelightfulSeqEntity[] $seqList
     * @return DelightfulSeqEntity[]
     */
    public function batchCreateSeq(array $seqList): array;

    /**
     * 返回 $userLocalMaxSeqId 之后的 $limit 条消息.
     * @return ClientSequenceResponse[]
     */
    public function getAccountSeqListByDelightfulId(DataIsolation $dataIsolation, int $userLocalMaxSeqId, int $limit): array;

    /**
     * 根据 app_message_id 拉取消息.
     * @return ClientSequenceResponse[]
     */
    public function getAccountSeqListByAppMessageId(DataIsolation $dataIsolation, string $appMessageId, string $pageToken, int $pageSize): array;

    /**
     * 返回最大消息的倒数 n 条序列.
     * @return ClientSequenceResponse[]
     */
    public function pullRecentMessage(DataIsolation $dataIsolation, int $userLocalMaxSeqId, int $limit): array;

    public function getSeqByMessageId(string $messageId): ?DelightfulSeqEntity;

    /**
     * @return ClientSequenceResponse[]
     */
    public function getConversationChatMessages(MessagesQueryDTO $messagesQueryDTO): array;

    /**
     * @return ClientSequenceResponse[]
     * @todo 挪到 delightful_chat_topic_messages 处理
     * 会话窗口滚动加载历史记录.
     * message_id= seq表的主键id,因此不需要单独对 message_id 加索引.
     */
    public function getConversationsChatMessages(MessagesQueryDTO $messagesQueryDTO, array $conversationIds): array;

    /**
     * 分组获取会话下最新的几条消息.
     */
    public function getConversationsMessagesGroupById(MessagesQueryDTO $messagesQueryDTO, array $conversationIds): array;

    /**
     * 获取收件方消息的状态变更流.
     * @return DelightfulSeqEntity[]
     */
    public function getReceiveMessagesStatusChange(array $referMessageIds, string $userId): array;

    /**
     * 获取发件方消息的状态变更流.
     * @return DelightfulSeqEntity[]
     */
    public function getSenderMessagesStatusChange(string $senderMessageId, string $userId): array;

    /**
     * @return ClientSequenceResponse[]
     */
    public function getConversationMessagesBySeqIds(array $messageIds, Order $order): array;

    public function getMessageReceiveList(string $messageId, string $delightfulId, ConversationType $userType): ?array;

    /**
     * Retrieve the sequence (seq) lists of both the sender and the receiver based on the $delightfulMessageId (generally used in the message editing scenario).
     */
    public function getBothSeqListByDelightfulMessageId(string $delightfulMessageId): array;

    /**
     * Optimized version: Group by object_id at MySQL level and return only the minimum seq_id record for each user.
     */
    public function getMinSeqListByDelightfulMessageId(string $delightfulMessageId): array;

    /**
     * 获取消息的撤回 seq.
     */
    public function getMessageRevokedSeq(string $messageId, DelightfulUserEntity $userEntity, ControlMessageType $controlMessageType): ?DelightfulSeqEntity;

    // 按类型获取会话中的seq
    public function getConversationSeqByType(string $delightfulId, string $conversationId, ControlMessageType $seqType): ?DelightfulSeqEntity;

    /**
     * @return DelightfulSeqEntity[]
     */
    public function batchGetSeqByMessageIds(array $messageIds): array;

    public function getSeqMessageByIds(array $ids);

    public function deleteSeqMessageByIds(array $seqIds): int;

    // 为了移除脏数据写的方法
    public function getSeqByDelightfulId(string $delightfulId, int $limit): array;

    // 为了移除脏数据写的方法
    public function getHasTrashMessageUsers(): array;

    public function updateSeqExtra(string $seqId, SeqExtra $seqExtra): bool;

    public function batchUpdateSeqStatus(array $seqIds, DelightfulMessageStatus $status): int;

    public function updateSeqRelation(DelightfulSeqEntity $seqEntity): bool;

    /**
     * 更新消息接收人列表.
     */
    public function updateReceiveList(DelightfulSeqEntity $seqEntity): bool;

    /**
     * Get sequences by conversation ID and seq IDs.
     * @param string $conversationId 会话ID
     * @param array $seqIds 序列ID数组
     * @return DelightfulSeqEntity[] 序列实体数组
     */
    public function getSequencesByConversationIdAndSeqIds(string $conversationId, array $seqIds): array;
}
