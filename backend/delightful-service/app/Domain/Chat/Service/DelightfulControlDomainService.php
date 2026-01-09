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
 * handlecontrolmessage相close.
 */
class DelightfulControlDomainService extends AbstractDomainService
{
    /**
     * return收itemmany waysitemmessagefinalreadstatus
     */
    public function getSenderMessageLatestReadStatus(string $senderMessageId, string $senderUserId): ?DelightfulSeqEntity
    {
        $senderSeqList = $this->delightfulSeqRepository->getSenderMessagesStatusChange($senderMessageId, $senderUserId);
        // toatreceive方come说,one sender_message_id byatstatuschange,maybewillhave多itemrecord,this处needmostbackstatus
        $userMessagesReadStatus = $this->getMessageLatestStatus([$senderMessageId], $senderSeqList);
        return $userMessagesReadStatus[$senderMessageId] ?? null;
    }

    /**
     * handle mq middleminutehairmessagealready读/alreadyviewmessage. thisthesemessageneed操asmessagesend者seq.
     */
    public function handlerMQReceiptSeq(DelightfulSeqEntity $receiveDelightfulSeqEntity): void
    {
        $controlMessageType = $receiveDelightfulSeqEntity->getSeqType();
        // according toalready读return执send方,parseoutcomemessagesend方info
        $receiveConversationId = $receiveDelightfulSeqEntity->getConversationId();
        $receiveConversationEntity = $this->delightfulConversationRepository->getConversationById($receiveConversationId);
        if ($receiveConversationEntity === null) {
            $this->logger->error(sprintf(
                'messageDispatch 收item方conversationnot存in $conversation_id:%s $delightfulSeqEntity:%s',
                $receiveConversationId,
                Json::encode($receiveDelightfulSeqEntity->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ));
            return;
        }
        // passreturn执send者quotemessageid,找tosend者messageid. (notcandirectlyusereceive者 sender_message_id field,thisisonenotgooddesign,随o clockcancel)
        $senderMessageId = $this->delightfulSeqRepository->getSeqByMessageId($receiveDelightfulSeqEntity->getReferMessageId())?->getSenderMessageId();
        if ($senderMessageId === null) {
            $this->logger->error(sprintf(
                'messageDispatch nothave找tosend方messageid $delightfulSeqEntity:%s $senderMessageId:%s',
                Json::encode($receiveDelightfulSeqEntity->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $senderMessageId
            ));
            return;
        }
        // nothave找tosend方conversationid
        $senderConversationId = $this->delightfulSeqRepository->getSeqByMessageId($senderMessageId)?->getConversationId();
        if ($senderConversationId) {
            $senderConversationEntity = $this->delightfulConversationRepository->getConversationById($senderConversationId);
        } else {
            $senderConversationEntity = null;
        }
        if ($senderConversationId === null || $senderConversationEntity === null) {
            $this->logger->error(sprintf(
                'messageDispatch nothave找tosend方conversationid $delightfulSeqEntity:%s',
                Json::encode($receiveDelightfulSeqEntity->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ));
            return;
        }

        $senderUserId = $senderConversationEntity->getUserId();
        $senderMessageId = $receiveDelightfulSeqEntity->getSenderMessageId();
        # thiswithinaddonedownminute布typelinelock,preventandhairmodifymessagereceivepersoncolumntable,造becomedataoverride.
        $spinLockKey = 'chat:seq:lock:' . $senderMessageId;
        $spinLockKeyOwner = random_bytes(8);
        try {
            if (! $this->redisLocker->spinLock($spinLockKey, $spinLockKeyOwner)) {
                // from旋fail
                $this->logger->error(sprintf(
                    'messageDispatch getmessagereceivepersoncolumntablefrom旋locktimeout $spinLockKey:%s $delightfulSeqEntity:%s',
                    $spinLockKey,
                    Json::encode($receiveDelightfulSeqEntity->toArray())
                ));
                ExceptionBuilder::throw(ChatErrorCode::DATA_WRITE_FAILED);
            }
            // geteachitemmessagefinalstatus,parseoutcomereceivepersoncolumntable,
            $senderLatestSeq = $this->getSenderMessageLatestReadStatus($senderMessageId, $senderUserId);
            $receiveUserEntity = $this->delightfulUserRepository->getUserByAccountAndOrganization(
                $receiveDelightfulSeqEntity->getObjectId(),
                $receiveDelightfulSeqEntity->getOrganizationCode()
            );
            if ($receiveUserEntity === null) {
                $this->logger->error(sprintf(
                    'messageDispatch return执messagenot找tomessagesend者 $delightfulSeqEntity:%s',
                    Json::encode($receiveDelightfulSeqEntity->toArray())
                ));
                return;
            }
            // not找toseq,or者messagealreadybewithdraw
            if ($senderLatestSeq === null || $senderLatestSeq->getSeqType() === ControlMessageType::RevokeMessage) {
                $this->logger->error(sprintf(
                    'messageDispatch return执messagenot找to seq,or者messagealreadybewithdraw $senderLatestSeq:%s $delightfulSeqEntity:%s',
                    Json::encode($senderLatestSeq?->toArray()),
                    Json::encode($receiveDelightfulSeqEntity->toArray())
                ));
                return;
            }
            $messageStatus = DelightfulMessageStatus::getMessageStatusByControlMessageType($controlMessageType);

            switch ($controlMessageType) {
                case ControlMessageType::SeenMessages:
                    # already读return执(扫oneeyemessage,toatnontextcomplextypemessage,nothaveviewdetail).
                    $senderReceiveList = $senderLatestSeq->getReceiveList();
                    if ($senderReceiveList === null) {
                        $this->logger->error(sprintf(
                            'messageDispatch messagereceivepersoncolumntablefornull $delightfulSeqEntity:%s',
                            Json::encode($receiveDelightfulSeqEntity->toArray())
                        ));
                        return;
                    }
                    // 1.judgeuserwhetherinnot读columntablemiddle.
                    $unreadList = $senderReceiveList->getUnreadList();
                    if (! in_array($receiveUserEntity->getUserId(), $unreadList, true)) {
                        $this->logger->error(sprintf(
                            'messageDispatch usernotinmessagenot读columntablemiddle(maybeotherdevicealready读) $unreadList:%s $delightfulSeqEntity:%s',
                            Json::encode($unreadList),
                            Json::encode($receiveDelightfulSeqEntity->toArray())
                        ));
                        return;
                    }
                    // 2. willalready读userfromnot读移toalready读
                    $key = array_search($receiveUserEntity->getUserId(), $unreadList, true);
                    if ($key !== false) {
                        unset($unreadList[$key]);
                        $unreadList = array_values($unreadList);
                    }
                    // updatealready读columntable
                    $seenList = $senderReceiveList->getSeenList();
                    $seenList[] = $receiveUserEntity->getUserId();
                    $senderReceiveList->setUnreadList($unreadList);
                    $senderReceiveList->setSeenList($seenList);
                    // formessagesend者generatenewseq,useatupdatemessagereceivepersoncolumntable
                    $senderLatestSeq->setReceiveList($senderReceiveList);
                    # updatealready读columntableend

                    $senderLatestSeq->setSeqType($controlMessageType);
                    $senderLatestSeq->setStatus($messageStatus);
                    $senderSeqData = $senderLatestSeq->toArray();
                    $senderSeqData['content'] = ['refer_message_ids' => [$senderMessageId]];
                    $senderSeenSeqEntity = SeqAssembler::generateStatusChangeSeqEntity($senderSeqData, $senderMessageId);
                    // byat存inbatchquantitywritesituation,thiswithinonlygenerateentity,notcallcreatemethod
                    $seqData = SeqAssembler::getInsertDataByEntity($senderSeenSeqEntity);
                    $seqData['app_message_id'] = $receiveDelightfulSeqEntity->getAppMessageId();
                    Db::transaction(function () use ($senderMessageId, $senderReceiveList, $seqData) {
                        // 写database,updatemessagesend方already读columntable.thisisfor复usemessage收hairchannel,notifycustomer端havenewalready读return执.
                        $this->delightfulSeqRepository->createSequence($seqData);
                        // updateoriginal chat_seq messagereceivepersoncolumntable. avoidpullhistorymessageo clock,to方already读messagealsoisdisplaynot读.
                        $originalSeq = $this->delightfulSeqRepository->getSeqByMessageId($senderMessageId);
                        if ($originalSeq !== null) {
                            $originalSeq->setReceiveList($senderReceiveList);
                            $this->delightfulSeqRepository->updateReceiveList($originalSeq);
                        } else {
                            $this->logger->error(sprintf(
                                'messageDispatch updateoriginal chat_seq fail,not找tooriginalmessage $senderMessageId:%s',
                                $senderMessageId
                            ));
                        }
                    });

                    // 3. asyncpushgivemessagesend方,havepersonalready读hehairoutmessage
                    $this->pushControlSequence($senderSeenSeqEntity);
                    break;
                case ControlMessageType::ReadMessage:
                default:
                    break;
            }
        } finally {
            // releaselock
            $this->redisLocker->release($spinLockKey, $spinLockKeyOwner);
        }
    }

    /**
     * handle mq middleminutehairwithdraw/editmessage. thisthesemessageis操asuserfrom己seq.
     * @throws Throwable
     */
    public function handlerMQUserSelfMessageChange(DelightfulSeqEntity $changeMessageStatusSeqEntity): void
    {
        $controlMessageType = $changeMessageStatusSeqEntity->getSeqType();
        // passreturn执send者quotemessageid,找tosend者messageid. (notcandirectlyusereceive者 sender_message_id field,thisisonenotgooddesign,随o clockcancel)
        $needChangeSeqEntity = $this->delightfulSeqRepository->getSeqByMessageId($changeMessageStatusSeqEntity->getReferMessageId());
        if ($needChangeSeqEntity === null) {
            $this->logger->error(sprintf(
                'messageDispatch nothave找tosend方messageid $delightfulSeqEntity:%s',
                Json::encode($changeMessageStatusSeqEntity->toArray())
            ));
            return;
        }
        # thiswithinaddonedownminute布typelinelock,preventandhair.
        $revokeMessageId = $needChangeSeqEntity->getSeqId();
        $spinLockKey = 'chat:seq:lock:' . $revokeMessageId;
        try {
            if (! $this->redisLocker->mutexLock($spinLockKey, $revokeMessageId)) {
                // mutually exclusivefail
                $this->logger->error(sprintf(
                    'messageDispatch withdrawor者editmessagefail $spinLockKey:%s $delightfulSeqEntity:%s',
                    $spinLockKey,
                    Json::encode($changeMessageStatusSeqEntity->toArray())
                ));
                ExceptionBuilder::throw(ChatErrorCode::DATA_WRITE_FAILED);
            }
            // updateoriginalmessagestatus
            $messageStatus = DelightfulMessageStatus::getMessageStatusByControlMessageType($controlMessageType);
            $this->delightfulSeqRepository->batchUpdateSeqStatus([$needChangeSeqEntity->getSeqId()], $messageStatus);
            // according to delightful_message_id 找to所havemessagereceive者
            $notifyAllReceiveSeqList = $this->batchCreateSeqByRevokeOrEditMessage($needChangeSeqEntity, $controlMessageType);
            // rowexceptuserfrom己,因foralready经提front
            $this->batchPushControlSeqList($notifyAllReceiveSeqList);
        } finally {
            // releaselock
            $this->redisLocker->release($spinLockKey, $revokeMessageId);
        }
    }

    /**
     * minutehairgroup chat/private chatmiddlewithdrawor者editmessage.
     * @return DelightfulSeqEntity[]
     */
    #[Transactional]
    public function batchCreateSeqByRevokeOrEditMessage(DelightfulSeqEntity $needChangeSeqEntity, ControlMessageType $controlMessageType): array
    {
        // get所have收item方seq
        $receiveSeqList = $this->delightfulSeqRepository->getBothSeqListByDelightfulMessageId($needChangeSeqEntity->getDelightfulMessageId());
        $receiveSeqList = array_column($receiveSeqList, null, 'object_id');
        // go掉from己,因forneedando clockresponse,already经single独generateseqandpush
        unset($receiveSeqList[$needChangeSeqEntity->getObjectId()]);
        $seqListCreateDTO = [];
        foreach ($receiveSeqList as $receiveSeq) {
            // withdrawallis收item方from己conversationwindowmiddlemessageid
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
