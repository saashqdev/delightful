<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Service;

use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Hyperf\Codec\Json;
use Hyperf\DbConnection\Annotation\Transactional;
use Hyperf\DbConnection\Db;
use Throwable;

/**
 * handle控制message相关.
 */
class DelightfulControlDomainService extends AbstractDomainService
{
    /**
     * return收件方多条message最终的阅读status
     */
    public function getSenderMessageLatestReadStatus(string $senderMessageId, string $senderUserId): ?DelightfulSeqEntity
    {
        $senderSeqList = $this->delightfulSeqRepository->getSenderMessagesStatusChange($senderMessageId, $senderUserId);
        // 对于receive方来说,一个 sender_message_id 由于status变化,可能会有多条记录,此处需要最后的status
        $userMessagesReadStatus = $this->getMessageLatestStatus([$senderMessageId], $senderSeqList);
        return $userMessagesReadStatus[$senderMessageId] ?? null;
    }

    /**
     * handle mq 中分发的message已读/已查看message. 这些message需要操作messagesend者的seq.
     */
    public function handlerMQReceiptSeq(DelightfulSeqEntity $receiveDelightfulSeqEntity): void
    {
        $controlMessageType = $receiveDelightfulSeqEntity->getSeqType();
        // according to已读回执的send方,parse出来messagesend方的info
        $receiveConversationId = $receiveDelightfulSeqEntity->getConversationId();
        $receiveConversationEntity = $this->delightfulConversationRepository->getConversationById($receiveConversationId);
        if ($receiveConversationEntity === null) {
            $this->logger->error(sprintf(
                'messageDispatch 收件方的conversation不存在 $conversation_id:%s $delightfulSeqEntity:%s',
                $receiveConversationId,
                Json::encode($receiveDelightfulSeqEntity->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ));
            return;
        }
        // 通过回执send者quote的messageid,找到send者的messageid. (不可直接usereceive者的 sender_message_id 字段,这是一个不好的design,随时cancel)
        $senderMessageId = $this->delightfulSeqRepository->getSeqByMessageId($receiveDelightfulSeqEntity->getReferMessageId())?->getSenderMessageId();
        if ($senderMessageId === null) {
            $this->logger->error(sprintf(
                'messageDispatch 没有找到send方的messageid $delightfulSeqEntity:%s $senderMessageId:%s',
                Json::encode($receiveDelightfulSeqEntity->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $senderMessageId
            ));
            return;
        }
        // 没有找到send方的conversationid
        $senderConversationId = $this->delightfulSeqRepository->getSeqByMessageId($senderMessageId)?->getConversationId();
        if ($senderConversationId) {
            $senderConversationEntity = $this->delightfulConversationRepository->getConversationById($senderConversationId);
        } else {
            $senderConversationEntity = null;
        }
        if ($senderConversationId === null || $senderConversationEntity === null) {
            $this->logger->error(sprintf(
                'messageDispatch 没有找到send方的conversationid $delightfulSeqEntity:%s',
                Json::encode($receiveDelightfulSeqEntity->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ));
            return;
        }

        $senderUserId = $senderConversationEntity->getUserId();
        $senderMessageId = $receiveDelightfulSeqEntity->getSenderMessageId();
        # 这里加一下分布式行锁,防止并发修改messagereceive人列表,造成数据override.
        $spinLockKey = 'chat:seq:lock:' . $senderMessageId;
        $spinLockKeyOwner = random_bytes(8);
        try {
            if (! $this->redisLocker->spinLock($spinLockKey, $spinLockKeyOwner)) {
                // 自旋fail
                $this->logger->error(sprintf(
                    'messageDispatch 获取messagereceive人列表的自旋锁超时 $spinLockKey:%s $delightfulSeqEntity:%s',
                    $spinLockKey,
                    Json::encode($receiveDelightfulSeqEntity->toArray())
                ));
                ExceptionBuilder::throw(ChatErrorCode::DATA_WRITE_FAILED);
            }
            // 获取每条message的最终status,parse出来receive人列表,
            $senderLatestSeq = $this->getSenderMessageLatestReadStatus($senderMessageId, $senderUserId);
            $receiveUserEntity = $this->delightfulUserRepository->getUserByAccountAndOrganization(
                $receiveDelightfulSeqEntity->getObjectId(),
                $receiveDelightfulSeqEntity->getOrganizationCode()
            );
            if ($receiveUserEntity === null) {
                $this->logger->error(sprintf(
                    'messageDispatch 回执message未找到messagesend者 $delightfulSeqEntity:%s',
                    Json::encode($receiveDelightfulSeqEntity->toArray())
                ));
                return;
            }
            // 没找到seq,或者message已被withdraw
            if ($senderLatestSeq === null || $senderLatestSeq->getSeqType() === ControlMessageType::RevokeMessage) {
                $this->logger->error(sprintf(
                    'messageDispatch 回执message没找到 seq,或者message已被withdraw $senderLatestSeq:%s $delightfulSeqEntity:%s',
                    Json::encode($senderLatestSeq?->toArray()),
                    Json::encode($receiveDelightfulSeqEntity->toArray())
                ));
                return;
            }
            $messageStatus = DelightfulMessageStatus::getMessageStatusByControlMessageType($controlMessageType);

            switch ($controlMessageType) {
                case ControlMessageType::SeenMessages:
                    # 已读回执(扫了一眼message,对于非文本的复杂typemessage,没有查看详情).
                    $senderReceiveList = $senderLatestSeq->getReceiveList();
                    if ($senderReceiveList === null) {
                        $this->logger->error(sprintf(
                            'messageDispatch messagereceive人列表为null $delightfulSeqEntity:%s',
                            Json::encode($receiveDelightfulSeqEntity->toArray())
                        ));
                        return;
                    }
                    // 1.判断user是否在未读列表中.
                    $unreadList = $senderReceiveList->getUnreadList();
                    if (! in_array($receiveUserEntity->getUserId(), $unreadList, true)) {
                        $this->logger->error(sprintf(
                            'messageDispatch user不在message未读列表中（可能其他设备已读） $unreadList:%s $delightfulSeqEntity:%s',
                            Json::encode($unreadList),
                            Json::encode($receiveDelightfulSeqEntity->toArray())
                        ));
                        return;
                    }
                    // 2. 将已读的user从未读移到已读
                    $key = array_search($receiveUserEntity->getUserId(), $unreadList, true);
                    if ($key !== false) {
                        unset($unreadList[$key]);
                        $unreadList = array_values($unreadList);
                    }
                    // 更新已读列表
                    $seenList = $senderReceiveList->getSeenList();
                    $seenList[] = $receiveUserEntity->getUserId();
                    $senderReceiveList->setUnreadList($unreadList);
                    $senderReceiveList->setSeenList($seenList);
                    // 为messagesend者generate新的seq,用于更新messagereceive人列表
                    $senderLatestSeq->setReceiveList($senderReceiveList);
                    # 更新已读列表end

                    $senderLatestSeq->setSeqType($controlMessageType);
                    $senderLatestSeq->setStatus($messageStatus);
                    $senderSeqData = $senderLatestSeq->toArray();
                    $senderSeqData['content'] = ['refer_message_ids' => [$senderMessageId]];
                    $senderSeenSeqEntity = SeqAssembler::generateStatusChangeSeqEntity($senderSeqData, $senderMessageId);
                    // 由于存在批量写入的情况,这里只generateentity,不callcreatemethod
                    $seqData = SeqAssembler::getInsertDataByEntity($senderSeenSeqEntity);
                    $seqData['app_message_id'] = $receiveDelightfulSeqEntity->getAppMessageId();
                    Db::transaction(function () use ($senderMessageId, $senderReceiveList, $seqData) {
                        // 写数据库,更新messagesend方的已读列表。这是为了复用message收发通道，notify客户端有新的已读回执。
                        $this->delightfulSeqRepository->createSequence($seqData);
                        // 更新原始 chat_seq 的messagereceive人列表。 避免拉取历史message时，对方已读的message还是显示未读。
                        $originalSeq = $this->delightfulSeqRepository->getSeqByMessageId($senderMessageId);
                        if ($originalSeq !== null) {
                            $originalSeq->setReceiveList($senderReceiveList);
                            $this->delightfulSeqRepository->updateReceiveList($originalSeq);
                        } else {
                            $this->logger->error(sprintf(
                                'messageDispatch 更新原始 chat_seq fail，未找到原始message $senderMessageId:%s',
                                $senderMessageId
                            ));
                        }
                    });

                    // 3. asyncpush给message的send方,有人已读了他发出的message
                    $this->pushControlSequence($senderSeenSeqEntity);
                    break;
                case ControlMessageType::ReadMessage:
                default:
                    break;
            }
        } finally {
            // 释放锁
            $this->redisLocker->release($spinLockKey, $spinLockKeyOwner);
        }
    }

    /**
     * handle mq 中分发的withdraw/editmessage. 这些message是操作user自己的seq.
     * @throws Throwable
     */
    public function handlerMQUserSelfMessageChange(DelightfulSeqEntity $changeMessageStatusSeqEntity): void
    {
        $controlMessageType = $changeMessageStatusSeqEntity->getSeqType();
        // 通过回执send者quote的messageid,找到send者的messageid. (不可直接usereceive者的 sender_message_id 字段,这是一个不好的design,随时cancel)
        $needChangeSeqEntity = $this->delightfulSeqRepository->getSeqByMessageId($changeMessageStatusSeqEntity->getReferMessageId());
        if ($needChangeSeqEntity === null) {
            $this->logger->error(sprintf(
                'messageDispatch 没有找到send方的messageid $delightfulSeqEntity:%s',
                Json::encode($changeMessageStatusSeqEntity->toArray())
            ));
            return;
        }
        # 这里加一下分布式行锁,防止并发.
        $revokeMessageId = $needChangeSeqEntity->getSeqId();
        $spinLockKey = 'chat:seq:lock:' . $revokeMessageId;
        try {
            if (! $this->redisLocker->mutexLock($spinLockKey, $revokeMessageId)) {
                // 互斥fail
                $this->logger->error(sprintf(
                    'messageDispatch withdraw或者editmessagefail $spinLockKey:%s $delightfulSeqEntity:%s',
                    $spinLockKey,
                    Json::encode($changeMessageStatusSeqEntity->toArray())
                ));
                ExceptionBuilder::throw(ChatErrorCode::DATA_WRITE_FAILED);
            }
            // 更新原始message的status
            $messageStatus = DelightfulMessageStatus::getMessageStatusByControlMessageType($controlMessageType);
            $this->delightfulSeqRepository->batchUpdateSeqStatus([$needChangeSeqEntity->getSeqId()], $messageStatus);
            // according to delightful_message_id 找到所有messagereceive者
            $notifyAllReceiveSeqList = $this->batchCreateSeqByRevokeOrEditMessage($needChangeSeqEntity, $controlMessageType);
            // 排除user自己,因为已经提前
            $this->batchPushControlSeqList($notifyAllReceiveSeqList);
        } finally {
            // 释放锁
            $this->redisLocker->release($spinLockKey, $revokeMessageId);
        }
    }

    /**
     * 分发group chat/private chat中的withdraw或者editmessage.
     * @return DelightfulSeqEntity[]
     */
    #[Transactional]
    public function batchCreateSeqByRevokeOrEditMessage(DelightfulSeqEntity $needChangeSeqEntity, ControlMessageType $controlMessageType): array
    {
        // 获取所有收件方的seq
        $receiveSeqList = $this->delightfulSeqRepository->getBothSeqListByDelightfulMessageId($needChangeSeqEntity->getDelightfulMessageId());
        $receiveSeqList = array_column($receiveSeqList, null, 'object_id');
        // 去掉自己,因为需要及时响应,已经单独generate了seq并push了
        unset($receiveSeqList[$needChangeSeqEntity->getObjectId()]);
        $seqListCreateDTO = [];
        foreach ($receiveSeqList as $receiveSeq) {
            // withdraw的都是收件方自己conversation窗口中的messageid
            $revokeMessageId = $receiveSeq['message_id'];
            $receiveSeq['status'] = DelightfulMessageStatus::Seen;
            $receiveSeq['seq_type'] = $controlMessageType->value;
            $receiveSeq['content'] = [
                'refer_message_id' => $revokeMessageId,
            ];
            $receiveSeq['refer_message_id'] = $revokeMessageId;
            $receiveSeq['receive_list'] = null;
            $seqListCreateDTO[] = SeqAssembler::generateStatusChangeSeqEntity(
                $receiveSeq,
                $revokeMessageId
            );
        }
        return $this->delightfulSeqRepository->batchCreateSeq($seqListCreateDTO);
    }
}
