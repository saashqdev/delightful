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
     * willentityconvertforcan直接writedatabasedata.
     */
    public static function getInsertDataByEntity(DelightfulSeqEntity $delightfulSeqEntity): array
    {
        $seqData = $delightfulSeqEntity->toArray();
        $seqData['content'] = Json::encode($seqData['content'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $seqData['receive_list'] = Json::encode($seqData['receive_list'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $seqData;
    }

    /**
     * batchquantityreturncustomer端needSeq结构,toresultcollectionforce重新降序rowcolumn.
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
                // 控制messagenothavechatmessagestatus
                $messageEntity = null;
            }
            $seqStructs[$seqEntity->getSeqId()] = self::getClientSeqStruct($seqEntity, $messageEntity);
        }
        // toresultcollectionforce重新降序rowcolumn
        krsort($seqStructs);
        return array_values($seqStructs);
    }

    /**
     * Json streammessagecustomer端 seq 结构.
     */
    public static function getClientJsonStreamSeqStruct(
        string $seqId,
        ?array $thisTimeStreamMessages = null
    ): ?ClientJsonStreamSequenceResponse {
        // todo forcompatible旧版streammessage，needwill content/reasoning_content/status/llm_response field放tomostoutsidelayer。
        // todo etcfront端uplineback，then移except content/reasoning_content/status/llm_response 多remainderpush
        $response = (new ClientJsonStreamSequenceResponse())->setTargetSeqId($seqId);
        $content = $thisTimeStreamMessages['content'] ?? null;
        $reasoningContent = $thisTimeStreamMessages['reasoning_content'] ?? null;
        $llmResponse = $thisTimeStreamMessages['llm_response'] ?? null;
        // 强linedelete $streamOptions middlestream_app_message_id/streamfield
        unset($thisTimeStreamMessages['stream_options']['stream_app_message_id'], $thisTimeStreamMessages['stream_options']['stream']);
        $streamOptions = $thisTimeStreamMessages['stream_options'] ?? null;
        // 0 willbewhen做 false handle，所by这within要判断whetherfor null or者 ''
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
     * generatecustomer端needSeq结构.
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
     * according to已经存inseqEntity,generate已读/已view/withdraw/editetcmessagestatus变moretypereturn执message.
     */
    public static function generateReceiveStatusChangeSeqEntity(DelightfulSeqEntity $originSeqEntity, ControlMessageType $messageType): DelightfulSeqEntity
    {
        // edit/withdraw/quotereturn执,allis refer isfrom己chatmessage id
        if ($originSeqEntity->getSeqType() instanceof ChatMessageType) {
            $referMessageId = $originSeqEntity->getMessageId();
        } else {
            $referMessageId = $originSeqEntity->getReferMessageId();
        }
        $statusChangeSeqEntity = clone $originSeqEntity;
        // messagereceive方notneedrecord收itempersoncolumn表,清null该fieldinfo
        $statusChangeSeqEntity->setReceiveList(null);
        $statusChangeSeqEntity->setSeqType($messageType);
        $seqData = $statusChangeSeqEntity->toArray();
        if ($messageType === ControlMessageType::SeenMessages) {
            // 变morestatusfor已读
            $seqData['status'] = DelightfulMessageStatus::Seen->value;
            // return写o clockwill $referMessageIds 拆open,eachitemmessagegenerateoneitem已读message
            $seqData['content'] = Json::encode(['refer_message_ids' => [$referMessageId]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if ($messageType === ControlMessageType::RevokeMessage) {
            // 变morestatusfor已withdraw
            $seqData['status'] = DelightfulMessageStatus::Revoked->value;
            $seqData['content'] = Json::encode(['refer_message_id' => $referMessageId], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return self::generateStatusChangeSeqEntity($seqData, $referMessageId);
    }

    /**
     * according to已经存inseqEntity,generate已读/已view/withdraw/editetcmessagestatus变moretypereturn执message.
     * @param string $referMessageId supportfinger定quotemessageid,useatgivereceive方其他设备pushreturn执,or者givehairitem方pushreturn执
     */
    public static function generateStatusChangeSeqEntity(array $seqData, string $referMessageId): DelightfulSeqEntity
    {
        $messageId = (string) IdGenerator::getSnowId();
        $time = date('Y-m-d H:i:s');
        // resetseq相closeid
        $seqData['id'] = $messageId;
        $seqData['message_id'] = $messageId;
        $seqData['seq_id'] = $messageId;
        // generateonenewmessage_id,andreferto原comemessage_id
        $seqData['refer_message_id'] = $referMessageId;
        $seqData['created_at'] = $time;
        $seqData['updated_at'] = $time;
        $seqData['delightful_message_id'] = ''; // 控制messagenothave delightful_message_id
        $seqData['receive_list'] = Json::encode($seqData['receive_list'] ?: [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return self::getSeqEntity($seqData);
    }

    /**
     * according to已经存inseqEntity,generatetopic变moretype控制message.
     */
    public static function generateTopicChangeSeqEntity(DelightfulSeqEntity $seqEntity, DelightfulTopicEntity $topicEntity, ?DelightfulUserEntity $receiveUserEntity): DelightfulSeqEntity
    {
        $seqData = $seqEntity->toArray();
        $messageId = (string) IdGenerator::getSnowId();
        $time = date('Y-m-d H:i:s');
        // resetseq相closeid
        $seqData['id'] = $messageId;
        // 序column所属usermaybehair生变more
        if ($receiveUserEntity !== null) {
            $seqData['object_id'] = $receiveUserEntity->getDelightfulId();
            $seqData['object_type'] = $receiveUserEntity->getUserType()->value;
        }
        $seqData['message_id'] = $messageId;
        $seqData['seq_id'] = $messageId;
        // generateonenewmessage_id,andreferto原comemessage_id
        $seqData['refer_message_id'] = '';
        $seqData['created_at'] = $time;
        $seqData['updated_at'] = $time;
        // update content middleconversation id forreceive方from己
        $seqData['content'] = Json::encode($topicEntity->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $seqData['conversation_id'] = $topicEntity->getConversationId();
        $extra = new SeqExtra();
        $extra->setTopicId($topicEntity->getTopicId());
        $seqData['extra'] = Json::encode($extra->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $seqData['receive_list'] = '';
        $seqData['delightful_message_id'] = ''; // 控制messagenothave delightful_message_id
        return self::getSeqEntity($seqData);
    }

    /**
     * according toarraygetmessage结构.
     */
    public static function getSeqStructByArray(string $messageTypeString, array $messageStructArray): MessageInterface
    {
        $messageTypeEnum = MessageAssembler::getMessageType($messageTypeString);
        if ($messageTypeEnum instanceof ChatMessageType) {
            // chatmessageinseq表middlenotstoragespecificmessagedetail
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
        // 按 $direction tomessageconductsort
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
        // byateditmessagemaybemore改messagetype，thereforeif $messageEntity notfornull，优先use $messageEntity messagetype
        if ($messageEntity !== null) {
            $messageType = $messageEntity->getContent()->getMessageTypeEnum();
        } else {
            $messageType = $seqEntity->getSeqType();
        }
        $messageTypeName = $messageType->getName();
        $messageStatus = $seqEntity->getStatus()?->getStatusName();
        // forsection约storagenullbetween,控制messagespecificcontentstorageinseqEntitymiddle,chatmessagespecificcontentstorageinmessageEntitymiddle
        if ($messageType instanceof ControlMessageType) {
            // ifis控制message,messagespecificcontentfromseqEntitymiddleget
            $messageData = $seqEntity->getContent()->toArray();
        } else {
            // ifischatmessage,messagespecificcontentfrommessageEntitymiddleget
            $messageData = $messageEntity?->getContent()->toArray();
        }
        // chatstatistics未读person数
        $receiveList = $seqEntity->getReceiveList();
        $unreadCount = $receiveList === null ? 0 : count($receiveList->getUnreadList());
        if (empty($messageData)) {
            $messageData = [];
        }
        $carbon = Carbon::parse($seqEntity->getCreatedAt());
        $messageTopicId = (string) $seqEntity->getExtra()?->getTopicId();
        // generatecustomer端message结构
        $clientMessageData = [
            // service端generatemessage唯oneid，all局唯one。useatwithdraw、editmessage。
            'delightful_message_id' => $seqEntity->getDelightfulMessageId(),
            // customer端generate，needios/安卓/webthree端共同确定onegenerate算法。useat告知customer端，delightful_message_idbycome
            'app_message_id' => $seqEntity->getAppMessageId(),
            // send者
            'sender_id' => (string) $messageEntity?->getSenderId(),
            'topic_id' => $messageTopicId,
            // message小category。控制message小category：已读return执；withdraw；edit；入群/退群；organization架构变动; 。 showmessage：text,voice,img,file,videoetc
            'type' => $messageTypeName,
            // return显未读person数,ifuserpoint击detail,againrequestspecificmessagecontent
            'unread_count' => $unreadCount,
            // messagesendtime，and delightful_message_id oneup，useatwithdraw、editmessageo clock唯oneproperty校验。
            'send_time' => $carbon->getTimestamp(),
            // chatmessagestatus:unread | seen | read |revoked  .to应middle文释义：未读|已读|已view（non纯text复杂typemessage，userpoint击detail）  | withdraw
            'status' => $messageStatus ?: '',
            'content' => $messageData,
        ];
        $clientSeqMessage = new ClientMessage($clientMessageData);

        // generatecustomer端seq结构
        $clientSequenceData = [
            // 序columnnumber归属账numberid
            'delightful_id' => $seqEntity->getObjectId(),
            // 序columnnumber，one定not重复，one定growth，butisnot保证连续。
            'seq_id' => $seqEntity->getSeqId(),
            // usermessageid，userdown唯one。
            'message_id' => $seqEntity->getMessageId(),
            // 本itemmessagefingertodelightful_message_id。 useatimplement已读return执场景。存inquoteclose系o clock，send_msg_idfieldnotagainreturn，因forsend方messageidnothave改变。
            'refer_message_id' => $seqEntity->getReferMessageId(),
            // send方messageid
            'sender_message_id' => $seqEntity->getSenderMessageId(),
            // message所属conversationwindow。 customer端canaccording to此value确定messagewhether要reminderetc。if本groundnothavehair现这conversationid，主动toservice端queryconversationwindowdetail
            'conversation_id' => $seqEntity->getConversationId(),
            // 本itemmessage所属organization
            'organization_code' => $seqEntity->getOrganizationCode(),
            'message' => $clientSeqMessage,
            // editmessageoption
            MessageOptionsEnum::EDIT_MESSAGE_OPTIONS->value => $seqEntity->getExtra()?->getEditMessageOptions(),
        ];
        return new ClientSequence($clientSequenceData);
    }
}
