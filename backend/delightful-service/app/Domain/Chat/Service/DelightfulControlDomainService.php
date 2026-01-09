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
 * handlecontrolmessagerelatedclose.
 */
class DelightfulControlDomainService extends AbstractDomainService
{
    /**
     * returnreceiveitemmany waysitemmessagefinalreadstatus
     */
    public function getSenderMessageLatestReadStatus(string $senderMessageId, string $senderUserId): ?DelightfulSeqEntity
    {
        $senderSeqList = $this->delightfulSeqRepository->getSenderMessagesStatusChange($senderMessageId, $senderUserId);
        // toatreceivesidecomesay,one sender_message_id byatstatuschange,maybewillhavemultipleitemrecord,thislocationneedmostbackstatus
        $userMessagesReadStatus = $this->getMessageLatestStatus([$senderMessageId], $senderSeqList);
        return $userMessagesReadStatus[$senderMessageId] ?? null;
    }

    /**
     * handle mq middleminutehairmessagealreadyread/alreadyviewmessage. thisthesemessageneedoperationasmessagesendpersonseq.
     */
    public function handlerMQReceiptSeq(DelightfulSeqEntity $receiveDelightfulSeqEntity): void
    {
        $controlMessageType = $receiveDelightfulSeqEntity->getSeqType();
        // according toalreadyreadreturnexecutesendside,parseoutcomemessagesendsideinfo
        $receiveConversationId = $receiveDelightfulSeqEntity->getConversationId();
        $receiveConversationEntity = $this->delightfulConversationRepository->getConversationById($receiveConversationId);
        if ($receiveConversationEntity === null) {
            $this->logger->error(sprintf(
                'messageDispatch receiveitemsideconversationnotexistsin $conversation_id:%s $delightfulSeqEntity:%s',
                $receiveConversationId,
                Json::encode($receiveDelightfulSeqEntity->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ));
            return;
        }
        // passreturnexecutesendpersonquotemessageid,findtosendpersonmessageid. (notcandirectlyusereceiveperson sender_message_id field,thisisonenotgooddesign,随o clockcancel)
        $senderMessageId = $this->delightfulSeqRepository->getSeqByMessageId($receiveDelightfulSeqEntity->getReferMessageId())?->getSenderMessageId();
        if ($senderMessageId === null) {
            $this->logger->error(sprintf(
                'messageDispatch nothavefindtosendsidemessageid $delightfulSeqEntity:%s $senderMessageId:%s',
                Json::encode($receiveDelightfulSeqEntity->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                $senderMessageId
            ));
            return;
        }
        // nothavefindtosendsideconversationid
        $senderConversationId = $this->delightfulSeqRepository->getSeqByMessageId($senderMessageId)?->getConversationId();
        if ($senderConversationId) {
            $senderConversationEntity = $this->delightfulConversationRepository->getConversationById($senderConversationId);
        } else {
            $senderConversationEntity = null;
        }
        if ($senderConversationId === null || $senderConversationEntity === null) {
            $this->logger->error(sprintf(
                'messageDispatch nothavefindtosendsideconversationid $delightfulSeqEntity:%s',
                Json::encode($receiveDelightfulSeqEntity->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ));
            return;
        }

        $senderUserId = $senderConversationEntity->getUserId();
        $senderMessageId = $receiveDelightfulSeqEntity->getSenderMessageId();
        # thiswithinaddonedownminutedistributetypelinelock,preventandhairmodifymessagereceivepersoncolumntable,createbecomedataoverride.
        $spinLockKey = 'chat:seq:lock:' . $senderMessageId;
        $spinLockKeyOwner = random_bytes(8);
        try {
            if (! $this->redisLocker->spinLock($spinLockKey, $spinLockKeyOwner)) {
                // fromrotatefail
                $this->logger->error(sprintf(
                    'messageDispatch getmessagereceivepersoncolumntablefromrotatelocktimeout $spinLockKey:%s $delightfulSeqEntity:%s',
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
                    'messageDispatch returnexecutemessagenotfindtomessagesendperson $delightfulSeqEntity:%s',
                    Json::encode($receiveDelightfulSeqEntity->toArray())
                ));
                return;
            }
            // notfindtoseq,orpersonmessagealreadybewithdraw
            if ($senderLatestSeq === null || $senderLatestSeq->getSeqType() === ControlMessageType::RevokeMessage) {
                $this->logger->error(sprintf(
                    'messageDispatch returnexecutemessagenotfindto seq,orpersonmessagealreadybewithdraw $senderLatestSeq:%s $delightfulSeqEntity:%s',
                    Json::encode($senderLatestSeq?->toArray()),
                    Json::encode($receiveDelightfulSeqEntity->toArray())
                ));
                return;
            }
            $messageStatus = DelightfulMessageStatus::getMessageStatusByControlMessageType($controlMessageType);

            switch ($controlMessageType) {
                case ControlMessageType::SeenMessages:
                    # alreadyreadreturnexecute(scanoneeyemessage,toatnontextcomplextypemessage,nothaveviewdetail).
                    $senderReceiveList = $senderLatestSeq->getReceiveList();
                    if ($senderReceiveList === null) {
                        $this->logger->error(sprintf(
                            'messageDispatch messagereceivepersoncolumntablefornull $delightfulSeqEntity:%s',
                            Json::encode($receiveDelightfulSeqEntity->toArray())
                        ));
                        return;
                    }
                    // 1.judgeuserwhetherinnotreadcolumntablemiddle.
                    $unreadList = $senderReceiveList->getUnreadList();
                    if (! in_array($receiveUserEntity->getUserId(), $unreadList, true)) {
                        $this->logger->error(sprintf(
                            'messageDispatch usernotinmessagenotreadcolumntablemiddle(maybeotherdevicealreadyread) $unreadList:%s $delightfulSeqEntity:%s',
                            Json::encode($unreadList),
                            Json::encode($receiveDelightfulSeqEntity->toArray())
                        ));
                        return;
                    }
                    // 2. willalreadyreaduserfromnotreadmovetoalreadyread
                    $key = array_search($receiveUserEntity->getUserId(), $unreadList, true);
                    if ($key !== false) {
                        unset($unreadList[$key]);
                        $unreadList = array_values($unreadList);
                    }
                    // updatealreadyreadcolumntable
                    $seenList = $senderReceiveList->getSeenList();
                    $seenList[] = $receiveUserEntity->getUserId();
                    $senderReceiveList->setUnreadList($unreadList);
                    $senderReceiveList->setSeenList($seenList);
                    // formessagesendpersongeneratenewseq,useatupdatemessagereceivepersoncolumntable
                    $senderLatestSeq->setReceiveList($senderReceiveList);
                    # updatealreadyreadcolumntableend

                    $senderLatestSeq->setSeqType($controlMessageType);
                    $senderLatestSeq->setStatus($messageStatus);
                    $senderSeqData = $senderLatestSeq->toArray();
                    $senderSeqData['content'] = ['refer_message_ids' => [$senderMessageId]];
                    $senderSeenSeqEntity = SeqAssembler::generateStatusChangeSeqEntity($senderSeqData, $senderMessageId);
                    // byatexistsinbatchquantitywritesituation,thiswithinonlygenerateentity,notcallcreatemethod
                    $seqData = SeqAssembler::getInsertDataByEntity($senderSeenSeqEntity);
                    $seqData['app_message_id'] = $receiveDelightfulSeqEntity->getAppMessageId();
                    Db::transaction(function () use ($senderMessageId, $senderReceiveList, $seqData) {
                        // writedatabase,updatemessagesendsidealreadyreadcolumntable.thisisforduplicateusemessagereceivehairchannel,notifycustomerclienthavenewalreadyreadreturnexecute.
                        $this->delightfulSeqRepository->createSequence($seqData);
                        // updateoriginal chat_seq messagereceivepersoncolumntable. avoidpullhistorymessageo clock,tosidealreadyreadmessagealsoisdisplaynotread.
                        $originalSeq = $this->delightfulSeqRepository->getSeqByMessageId($senderMessageId);
                        if ($originalSeq !== null) {
                            $originalSeq->setReceiveList($senderReceiveList);
                            $this->delightfulSeqRepository->updateReceiveList($originalSeq);
                        } else {
                            $this->logger->error(sprintf(
                                'messageDispatch updateoriginal chat_seq fail,notfindtooriginalmessage $senderMessageId:%s',
                                $senderMessageId
                            ));
                        }
                    });

                    // 3. asyncpushgivemessagesendside,havepersonalreadyreadhehairoutmessage
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
     * handle mq middleminutehairwithdraw/editmessage. thisthesemessageisoperationasuserfromselfseq.
     * @throws Throwable
     */
    public function handlerMQUserSelfMessageChange(DelightfulSeqEntity $changeMessageStatusSeqEntity): void
    {
        $controlMessageType = $changeMessageStatusSeqEntity->getSeqType();
        // passreturnexecutesendpersonquotemessageid,findtosendpersonmessageid. (notcandirectlyusereceiveperson sender_message_id field,thisisonenotgooddesign,随o clockcancel)
        $needChangeSeqEntity = $this->delightfulSeqRepository->getSeqByMessageId($changeMessageStatusSeqEntity->getReferMessageId());
        if ($needChangeSeqEntity === null) {
            $this->logger->error(sprintf(
                'messageDispatch nothavefindtosendsidemessageid $delightfulSeqEntity:%s',
                Json::encode($changeMessageStatusSeqEntity->toArray())
            ));
            return;
        }
        # thiswithinaddonedownminutedistributetypelinelock,preventandhair.
        $revokeMessageId = $needChangeSeqEntity->getSeqId();
        $spinLockKey = 'chat:seq:lock:' . $revokeMessageId;
        try {
            if (! $this->redisLocker->mutexLock($spinLockKey, $revokeMessageId)) {
                // mutually exclusivefail
                $this->logger->error(sprintf(
                    'messageDispatch withdraworpersoneditmessagefail $spinLockKey:%s $delightfulSeqEntity:%s',
                    $spinLockKey,
                    Json::encode($changeMessageStatusSeqEntity->toArray())
                ));
                ExceptionBuilder::throw(ChatErrorCode::DATA_WRITE_FAILED);
            }
            // updateoriginalmessagestatus
            $messageStatus = DelightfulMessageStatus::getMessageStatusByControlMessageType($controlMessageType);
            $this->delightfulSeqRepository->batchUpdateSeqStatus([$needChangeSeqEntity->getSeqId()], $messageStatus);
            // according to delightful_message_id findto havemessagereceiveperson
            $notifyAllReceiveSeqList = $this->batchCreateSeqByRevokeOrEditMessage($needChangeSeqEntity, $controlMessageType);
            // rowexceptuserfromself,factorforalreadyalreadysubmitfront
            $this->batchPushControlSeqList($notifyAllReceiveSeqList);
        } finally {
            // releaselock
            $this->redisLocker->release($spinLockKey, $revokeMessageId);
        }
    }

    /**
     * minutehairgroup chat/private chatmiddlewithdraworpersoneditmessage.
     * @return DelightfulSeqEntity[]
     */
    #[Transactional]
    public function batchCreateSeqByRevokeOrEditMessage(DelightfulSeqEntity $needChangeSeqEntity, ControlMessageType $controlMessageType): array
    {
        // get havereceiveitemsideseq
        $receiveSeqList = $this->delightfulSeqRepository->getBothSeqListByDelightfulMessageId($needChangeSeqEntity->getDelightfulMessageId());
        $receiveSeqList = array_column($receiveSeqList, null, 'object_id');
        // godropfromself,factorforneedando clockresponse,alreadyalreadysingleuniquegenerateseqandpush
        unset($receiveSeqList[$needChangeSeqEntity->getObjectId()]);
        $seqListCreateDTO = [];
        foreach ($receiveSeqList as $receiveSeq) {
            // withdrawallisreceiveitemsidefromselfconversationwindowmiddlemessageid
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
