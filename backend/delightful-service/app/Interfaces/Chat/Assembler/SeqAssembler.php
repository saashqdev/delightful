<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Assembler;

use App\Domain\Chat\DTO\Message\EmptyMessage;
use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamMessageStatus;
use App\Domain\Chat\DTO\Response\ClientJsonStreamSequenceResponse;
use App\Domain\Chat\DTO\Response\ClientSequenceResponse;
use App\Domain\Chat\DTO\Response\Common\ClientMessage;
use App\Domain\Chat\DTO\Response\Common\ClientSequence;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\DelightfulTopicEntity;
use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\MessageOptionsEnum;
use App\Domain\Chat\Entity\ValueObject\SocketEventType;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Constants\Order;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Carbon\Carbon;
use Hyperf\Codec\Json;
use Throwable;

class SeqAssembler
{
    public static function getSeqEntity(array $seqInfo): DelightfulSeqEntity
    {
        return new DelightfulSeqEntity($seqInfo);
    }

    /**
     * willentityconvertforcandirectlywritedatabasedata.
     */
    public static function getInsertDataByEntity(DelightfulSeqEntity $delightfulSeqEntity): array
    {
        $seqData = $delightfulSeqEntity->toArray();
        $seqData['content'] = Json::encode($seqData['content'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $seqData['receive_list'] = Json::encode($seqData['receive_list'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $seqData;
    }

    /**
     * batchquantityreturncustomerclientneedSeqstructure,toresultcollectionforcereloadnewdescendingrowcolumn.
     * @return ClientSequenceResponse[]
     */
    public static function getClientSeqStructs(array $seqInfos, array $messageInfos): array
    {
        $seqStructs = [];
        $messageInfos = array_column($messageInfos, null, 'delightful_message_id');
        foreach ($seqInfos as $seqInfo) {
            $seqEntity = self::getSeqEntity($seqInfo);
            if ($seqEntity->getSeqType() instanceof ChatMessageType) {
                $messageInfo = $messageInfos[$seqInfo['delightful_message_id']] ?? [];
                $messageEntity = MessageAssembler::getMessageEntity($messageInfo);
            } else {
                // controlmessagenothavechatmessagestatus
                $messageEntity = null;
            }
            $seqStructs[$seqEntity->getSeqId()] = self::getClientSeqStruct($seqEntity, $messageEntity);
        }
        // toresultcollectionforcereloadnewdescendingrowcolumn
        krsort($seqStructs);
        return array_values($seqStructs);
    }

    /**
     * Json streammessagecustomerclient seq structure.
     */
    public static function getClientJsonStreamSeqStruct(
        string $seqId,
        ?array $thisTimeStreamMessages = null
    ): ?ClientJsonStreamSequenceResponse {
        // todo forcompatibleoldversionstreammessage,needwill content/reasoning_content/status/llm_response fieldputtomostoutsidelayer.
        // todo etcfrontclientuplineback,thenmoveexcept content/reasoning_content/status/llm_response multipleremainderpush
        $response = (new ClientJsonStreamSequenceResponse())->setTargetSeqId($seqId);
        $content = $thisTimeStreamMessages['content'] ?? null;
        $reasoningContent = $thisTimeStreamMessages['reasoning_content'] ?? null;
        $llmResponse = $thisTimeStreamMessages['llm_response'] ?? null;
        // stronglinedelete $streamOptions middlestream_app_message_id/streamfield
        unset($thisTimeStreamMessages['stream_options']['stream_app_message_id'], $thisTimeStreamMessages['stream_options']['stream']);
        $streamOptions = $thisTimeStreamMessages['stream_options'] ?? null;
        // 0 willbewhenmake false handle, bythiswithinwantjudgewhetherfor null orperson ''
        if ($content !== null && $content !== '') {
            $response->setContent($content);
        }
        if ($llmResponse !== null && $llmResponse !== '') {
            $response->setLlmResponse($llmResponse);
        }
        if ($reasoningContent !== null && $reasoningContent !== '') {
            // byfrontprocesshave reasoning_content o clockalsowillpush content fornullstringdata
            $response->setReasoningContent($reasoningContent);
        }
        if (isset($streamOptions['status'])) {
            $response->setStatus($streamOptions['status']);
        } else {
            $response->setStatus(StreamMessageStatus::Processing);
        }
        $response->setStreams($thisTimeStreamMessages);
        return $response;
    }

    /**
     * generatecustomerclientneedSeqstructure.
     */
    public static function getClientSeqStruct(
        DelightfulSeqEntity $seqEntity,
        ?DelightfulMessageEntity $messageEntity = null
    ): ClientSequenceResponse {
        $clientSequence = self::getClientSequence($seqEntity, $messageEntity);
        return new ClientSequenceResponse([
            'type' => 'seq',
            'seq' => $clientSequence,
        ]);
    }

    /**
     * according toalreadyalready existsinseqEntity,generatealreadyread/alreadyview/withdraw/editetcmessagestatuschangemoretypereturnexecutemessage.
     */
    public static function generateReceiveStatusChangeSeqEntity(DelightfulSeqEntity $originSeqEntity, ControlMessageType $messageType): DelightfulSeqEntity
    {
        // edit/withdraw/quotereturnexecute,allis refer isfromselfchatmessage id
        if ($originSeqEntity->getSeqType() instanceof ChatMessageType) {
            $referMessageId = $originSeqEntity->getMessageId();
        } else {
            $referMessageId = $originSeqEntity->getReferMessageId();
        }
        $statusChangeSeqEntity = clone $originSeqEntity;
        // messagereceivesidenotneedrecordreceiveitempersoncolumntable,clearnullthefieldinfo
        $statusChangeSeqEntity->setReceiveList(null);
        $statusChangeSeqEntity->setSeqType($messageType);
        $seqData = $statusChangeSeqEntity->toArray();
        if ($messageType === ControlMessageType::SeenMessages) {
            // changemorestatusforalreadyread
            $seqData['status'] = DelightfulMessageStatus::Seen->value;
            // returnwriteo clockwill $referMessageIds 拆open,eachitemmessagegenerateoneitemalreadyreadmessage
            $seqData['content'] = Json::encode(['refer_message_ids' => [$referMessageId]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if ($messageType === ControlMessageType::RevokeMessage) {
            // changemorestatusforalreadywithdraw
            $seqData['status'] = DelightfulMessageStatus::Revoked->value;
            $seqData['content'] = Json::encode(['refer_message_id' => $referMessageId], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return self::generateStatusChangeSeqEntity($seqData, $referMessageId);
    }

    /**
     * according toalreadyalready existsinseqEntity,generatealreadyread/alreadyview/withdraw/editetcmessagestatuschangemoretypereturnexecutemessage.
     * @param string $referMessageId supportfingersetquotemessageid,useatgivereceivesideotherdevicepushreturnexecute,orpersongivehairitemsidepushreturnexecute
     */
    public static function generateStatusChangeSeqEntity(array $seqData, string $referMessageId): DelightfulSeqEntity
    {
        $messageId = (string) IdGenerator::getSnowId();
        $time = date('Y-m-d H:i:s');
        // resetseqrelatedcloseid
        $seqData['id'] = $messageId;
        $seqData['message_id'] = $messageId;
        $seqData['seq_id'] = $messageId;
        // generateonenewmessage_id,andrefertooriginalcomemessage_id
        $seqData['refer_message_id'] = $referMessageId;
        $seqData['created_at'] = $time;
        $seqData['updated_at'] = $time;
        $seqData['delightful_message_id'] = ''; // controlmessagenothave delightful_message_id
        $seqData['receive_list'] = Json::encode($seqData['receive_list'] ?: [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return self::getSeqEntity($seqData);
    }

    /**
     * according toalreadyalready existsinseqEntity,generatetopicchangemoretypecontrolmessage.
     */
    public static function generateTopicChangeSeqEntity(DelightfulSeqEntity $seqEntity, DelightfulTopicEntity $topicEntity, ?DelightfulUserEntity $receiveUserEntity): DelightfulSeqEntity
    {
        $seqData = $seqEntity->toArray();
        $messageId = (string) IdGenerator::getSnowId();
        $time = date('Y-m-d H:i:s');
        // resetseqrelatedcloseid
        $seqData['id'] = $messageId;
        // sequencecolumnbelong tousermaybehairchange occursmore
        if ($receiveUserEntity !== null) {
            $seqData['object_id'] = $receiveUserEntity->getDelightfulId();
            $seqData['object_type'] = $receiveUserEntity->getUserType()->value;
        }
        $seqData['message_id'] = $messageId;
        $seqData['seq_id'] = $messageId;
        // generateonenewmessage_id,andrefertooriginalcomemessage_id
        $seqData['refer_message_id'] = '';
        $seqData['created_at'] = $time;
        $seqData['updated_at'] = $time;
        // update content middleconversation id forreceivesidefromself
        $seqData['content'] = Json::encode($topicEntity->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $seqData['conversation_id'] = $topicEntity->getConversationId();
        $extra = new SeqExtra();
        $extra->setTopicId($topicEntity->getTopicId());
        $seqData['extra'] = Json::encode($extra->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $seqData['receive_list'] = '';
        $seqData['delightful_message_id'] = ''; // controlmessagenothave delightful_message_id
        return self::getSeqEntity($seqData);
    }

    /**
     * according toarraygetmessagestructure.
     */
    public static function getSeqStructByArray(string $messageTypeString, array $messageStructArray): MessageInterface
    {
        $messageTypeEnum = MessageAssembler::getMessageType($messageTypeString);
        if ($messageTypeEnum instanceof ChatMessageType) {
            // chatmessageinseqtablemiddlenotstoragespecificmessagedetail
            return new EmptyMessage();
        }
        try {
            return MessageAssembler::getControlMessageStruct($messageTypeEnum, $messageStructArray);
        } catch (Throwable $exception) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, throwable: $exception);
        }
    }

    /**
     * @param ClientSequenceResponse[] $clientSequenceResponses
     */
    public static function sortSeqList(array $clientSequenceResponses, Order $order): array
    {
        // by $direction tomessageconductsort
        if ($order === Order::Desc) {
            usort($clientSequenceResponses, function (ClientSequenceResponse $a, ClientSequenceResponse $b) {
                return $b->getSeq()->getSeqId() <=> $a->getSeq()->getSeqId();
            });
        } else {
            usort($clientSequenceResponses, function (ClientSequenceResponse $a, ClientSequenceResponse $b) {
                return $a->getSeq()->getSeqId() <=> $b->getSeq()->getSeqId();
            });
        }
        return $clientSequenceResponses;
    }

    /**
     * Get corresponding Socket event type based on sequence entity.
     */
    public static function getSocketEventType(DelightfulSeqEntity $seqEntity): SocketEventType
    {
        if ($seqEntity->getSeqType() instanceof ControlMessageType) {
            return SocketEventType::Control;
        }
        return SocketEventType::Chat;
    }

    private static function getClientSequence(DelightfulSeqEntity $seqEntity, ?DelightfulMessageEntity $messageEntity = null): ClientSequence
    {
        // byateditmessagemaybemorechangemessagetype,thereforeif $messageEntity notfornull,priorityuse $messageEntity messagetype
        if ($messageEntity !== null) {
            $messageType = $messageEntity->getContent()->getMessageTypeEnum();
        } else {
            $messageType = $seqEntity->getSeqType();
        }
        $messageTypeName = $messageType->getName();
        $messageStatus = $seqEntity->getStatus()?->getStatusName();
        // forsection约storagenullbetween,controlmessagespecificcontentstorageinseqEntitymiddle,chatmessagespecificcontentstorageinmessageEntitymiddle
        if ($messageType instanceof ControlMessageType) {
            // ifiscontrolmessage,messagespecificcontentfromseqEntitymiddleget
            $messageData = $seqEntity->getContent()->toArray();
        } else {
            // ifischatmessage,messagespecificcontentfrommessageEntitymiddleget
            $messageData = $messageEntity?->getContent()->toArray();
        }
        // chatstatisticsnotreadpersoncount
        $receiveList = $seqEntity->getReceiveList();
        $unreadCount = $receiveList === null ? 0 : count($receiveList->getUnreadList());
        if (empty($messageData)) {
            $messageData = [];
        }
        $carbon = Carbon::parse($seqEntity->getCreatedAt());
        $messageTopicId = (string) $seqEntity->getExtra()?->getTopicId();
        // generatecustomerclientmessagestructure
        $clientMessageData = [
            // serviceclientgeneratemessageuniqueoneid,alllocally uniqueone.useatwithdraw,editmessage.
            'delightful_message_id' => $seqEntity->getDelightfulMessageId(),
            // customerclientgenerate,needios/Android/webthreeclient commoncertainonegeneratealgorithm.useatinformcustomerclient,delightful_message_idbycome
            'app_message_id' => $seqEntity->getAppMessageId(),
            // sendperson
            'sender_id' => (string) $messageEntity?->getSenderId(),
            'topic_id' => $messageTopicId,
            // messagesmallcategory.controlmessagesmallcategory:alreadyreadreturnexecute;withdraw;edit;join group/leave group;organizationarchitecture change; . showmessage:text,voice,img,file,videoetc
            'type' => $messageTypeName,
            // returndisplaynotreadpersoncount,ifuserpoint击detail,againrequestspecificmessagecontent
            'unread_count' => $unreadCount,
            // messagesendtime,and delightful_message_id oneup,useatwithdraw,editmessageo clockuniqueonepropertyvalidation.
            'send_time' => $carbon->getTimestamp(),
            // chatmessagestatus:unread | seen | read |revoked  .toshouldmiddletext explanation:notread|alreadyread|alreadyview(nonpuretextcomplextypemessage,userpoint击detail)  | withdraw
            'status' => $messageStatus ?: '',
            'content' => $messageData,
        ];
        $clientSeqMessage = new ClientMessage($clientMessageData);

        // generatecustomerclientseqstructure
        $clientSequenceData = [
            // sequencecolumnnumberbelong toaccountnumberid
            'delightful_id' => $seqEntity->getObjectId(),
            // sequencecolumnnumber,onesetnotduplicate,onesetgrowth,butisnotguaranteecontinuous.
            'seq_id' => $seqEntity->getSeqId(),
            // usermessageid,userdownuniqueone.
            'message_id' => $seqEntity->getMessageId(),
            // thisitemmessagefingertodelightful_message_id. useatimplementalreadyreadreturnexecutescenario.existsinquoteclosesystemo clock,send_msg_idfieldnotagainreturn,factorforsendsidemessageidnothavealter.
            'refer_message_id' => $seqEntity->getReferMessageId(),
            // sendsidemessageid
            'sender_message_id' => $seqEntity->getSenderMessageId(),
            // messagebelong toconversationwindow. customerclientcanaccording tothisvaluecertainmessagewhetherwantreminderetc.ifthisgroundnothavehairshowthisconversationid,activetoserviceclientqueryconversationwindowdetail
            'conversation_id' => $seqEntity->getConversationId(),
            // thisitemmessagebelong toorganization
            'organization_code' => $seqEntity->getOrganizationCode(),
            'message' => $clientSeqMessage,
            // editmessageoption
            MessageOptionsEnum::EDIT_MESSAGE_OPTIONS->value => $seqEntity->getExtra()?->getEditMessageOptions(),
        ];
        return new ClientSequence($clientSequenceData);
    }
}
