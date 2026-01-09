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
     * return $userLocalMaxSeqId ofback $limit itemmessage.
     * @return ClientSequenceResponse[]
     */
    public function getAccountSeqListByDelightfulId(DataIsolation $dataIsolation, int $userLocalMaxSeqId, int $limit): array;

    /**
     * according to app_message_id pullmessage.
     * @return ClientSequenceResponse[]
     */
    public function getAccountSeqListByAppMessageId(DataIsolation $dataIsolation, string $appMessageId, string $pageToken, int $pageSize): array;

    /**
     * returnmostbigmessagecountdown n itemsequencecolumn.
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
     * @todo 挪to delightful_chat_topic_messages process
     * sessionwindowscrollloadhistoryrecord.
     * message_id= seqtableprimary keyid,thereforenotneedsingle独to message_id addindex.
     */
    public function getConversationsChatMessages(MessagesQueryDTO $messagesQueryDTO, array $conversationIds): array;

    /**
     * minutegroupgetsessiondownmostnewseveralitemmessage.
     */
    public function getConversationsMessagesGroupById(MessagesQueryDTO $messagesQueryDTO, array $conversationIds): array;

    /**
     * getreceiveitemsidemessagestatuschangemorestream.
     * @return DelightfulSeqEntity[]
     */
    public function getReceiveMessagesStatusChange(array $referMessageIds, string $userId): array;

    /**
     * gethairitemsidemessagestatuschangemorestream.
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
     * getmessagewithdraw seq.
     */
    public function getMessageRevokedSeq(string $messageId, DelightfulUserEntity $userEntity, ControlMessageType $controlMessageType): ?DelightfulSeqEntity;

    // bytypegetsessionmiddleseq
    public function getConversationSeqByType(string $delightfulId, string $conversationId, ControlMessageType $seqType): ?DelightfulSeqEntity;

    /**
     * @return DelightfulSeqEntity[]
     */
    public function batchGetSeqByMessageIds(array $messageIds): array;

    public function getSeqMessageByIds(array $ids);

    public function deleteSeqMessageByIds(array $seqIds): int;

    // formoveexcept脏datawritemethod
    public function getSeqByDelightfulId(string $delightfulId, int $limit): array;

    // formoveexcept脏datawritemethod
    public function getHasTrashMessageUsers(): array;

    public function updateSeqExtra(string $seqId, SeqExtra $seqExtra): bool;

    public function batchUpdateSeqStatus(array $seqIds, DelightfulMessageStatus $status): int;

    public function updateSeqRelation(DelightfulSeqEntity $seqEntity): bool;

    /**
     * updatemessagereceivepersonlist.
     */
    public function updateReceiveList(DelightfulSeqEntity $seqEntity): bool;

    /**
     * Get sequences by conversation ID and seq IDs.
     * @param string $conversationId sessionID
     * @param array $seqIds sequencecolumnIDarray
     * @return DelightfulSeqEntity[] sequencecolumnactualbodyarray
     */
    public function getSequencesByConversationIdAndSeqIds(string $conversationId, array $seqIds): array;
}
