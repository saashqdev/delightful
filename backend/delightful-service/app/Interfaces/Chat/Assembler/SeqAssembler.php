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
     * 将entityconvert为can直接writedatabase的data.
     */
    public static function getInsertDataByEntity(DelightfulSeqEntity $delightfulSeqEntity): array
    {
        $seqData = $delightfulSeqEntity->toArray();
        $seqData['content'] = Json::encode($seqData['content'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $seqData['receive_list'] = Json::encode($seqData['receive_list'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $seqData;
    }

    /**
     * 批quantityreturn客户端need的Seq结构,对result集force重新降序rowcolumn.
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
                // 控制messagenothavechatmessage的status
                $messageEntity = null;
            }
            $seqStructs[$seqEntity->getSeqId()] = self::getClientSeqStruct($seqEntity, $messageEntity);
        }
        // 对result集force重新降序rowcolumn
        krsort($seqStructs);
        return array_values($seqStructs);
    }

    /**
     * Json streammessage的客户端 seq 结构.
     */
    public static function getClientJsonStreamSeqStruct(
        string $seqId,
        ?array $thisTimeStreamMessages = null
    ): ?ClientJsonStreamSequenceResponse {
        // todo 为了compatible旧版streammessage，need将 content/reasoning_content/status/llm_response field放tomostoutsidelayer。
        // todo etcfront端uplineback，then移except content/reasoning_content/status/llm_response 的多余push
        $response = (new ClientJsonStreamSequenceResponse())->setTargetSeqId($seqId);
        $content = $thisTimeStreamMessages['content'] ?? null;
        $reasoningContent = $thisTimeStreamMessages['reasoning_content'] ?? null;
        $llmResponse = $thisTimeStreamMessages['llm_response'] ?? null;
        // 强linedelete $streamOptions middle的stream_app_message_id/streamfield
        unset($thisTimeStreamMessages['stream_options']['stream_app_message_id'], $thisTimeStreamMessages['stream_options']['stream']);
        $streamOptions = $thisTimeStreamMessages['stream_options'] ?? null;
        // 0 willbewhen做 false handle，所by这within要判断whether为 null or者 ''
        if ($content !== null && $content !== '') {
            $response->setContent($content);
        }
        if ($llmResponse !== null && $llmResponse !== '') {
            $response->setLlmResponse($llmResponse);
        }
        if ($reasoningContent !== null && $reasoningContent !== '') {
            // byfront的processhave reasoning_content o clockalsowillpush content 为nullstring的data
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
     * generate客户端need的Seq结构.
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
     * according to已经存in的seqEntity,generate已读/已查看/withdraw/editetcmessagestatus变moretype的回执message.
     */
    public static function generateReceiveStatusChangeSeqEntity(DelightfulSeqEntity $originSeqEntity, ControlMessageType $messageType): DelightfulSeqEntity
    {
        // edit/withdraw/quote的回执,all是 refer is自己chat的message id
        if ($originSeqEntity->getSeqType() instanceof ChatMessageType) {
            $referMessageId = $originSeqEntity->getMessageId();
        } else {
            $referMessageId = $originSeqEntity->getReferMessageId();
        }
        $statusChangeSeqEntity = clone $originSeqEntity;
        // message的receive方notneedrecord收item人column表,清null该fieldinfo
        $statusChangeSeqEntity->setReceiveList(null);
        $statusChangeSeqEntity->setSeqType($messageType);
        $seqData = $statusChangeSeqEntity->toArray();
        if ($messageType === ControlMessageType::SeenMessages) {
            // 变morestatus为已读
            $seqData['status'] = DelightfulMessageStatus::Seen->value;
            // 回写o clock将 $referMessageIds 拆开,eachitemmessagegenerate一item已读message
            $seqData['content'] = Json::encode(['refer_message_ids' => [$referMessageId]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if ($messageType === ControlMessageType::RevokeMessage) {
            // 变morestatus为已withdraw
            $seqData['status'] = DelightfulMessageStatus::Revoked->value;
            $seqData['content'] = Json::encode(['refer_message_id' => $referMessageId], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return self::generateStatusChangeSeqEntity($seqData, $referMessageId);
    }

    /**
     * according to已经存in的seqEntity,generate已读/已查看/withdraw/editetcmessagestatus变moretype的回执message.
     * @param string $referMessageId supportfinger定quote的messageid,useat给receive方的其他设备push回执,or者给hairitem方push回执
     */
    public static function generateStatusChangeSeqEntity(array $seqData, string $referMessageId): DelightfulSeqEntity
    {
        $messageId = (string) IdGenerator::getSnowId();
        $time = date('Y-m-d H:i:s');
        // resetseq的相关id
        $seqData['id'] = $messageId;
        $seqData['message_id'] = $messageId;
        $seqData['seq_id'] = $messageId;
        // generate一newmessage_id,并referto原来的message_id
        $seqData['refer_message_id'] = $referMessageId;
        $seqData['created_at'] = $time;
        $seqData['updated_at'] = $time;
        $seqData['delightful_message_id'] = ''; // 控制messagenothave delightful_message_id
        $seqData['receive_list'] = Json::encode($seqData['receive_list'] ?: [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return self::getSeqEntity($seqData);
    }

    /**
     * according to已经存in的seqEntity,generatetopic变moretype的控制message.
     */
    public static function generateTopicChangeSeqEntity(DelightfulSeqEntity $seqEntity, DelightfulTopicEntity $topicEntity, ?DelightfulUserEntity $receiveUserEntity): DelightfulSeqEntity
    {
        $seqData = $seqEntity->toArray();
        $messageId = (string) IdGenerator::getSnowId();
        $time = date('Y-m-d H:i:s');
        // resetseq的相关id
        $seqData['id'] = $messageId;
        // 序column所属user可能hair生变more
        if ($receiveUserEntity !== null) {
            $seqData['object_id'] = $receiveUserEntity->getDelightfulId();
            $seqData['object_type'] = $receiveUserEntity->getUserType()->value;
        }
        $seqData['message_id'] = $messageId;
        $seqData['seq_id'] = $messageId;
        // generate一newmessage_id,并referto原来的message_id
        $seqData['refer_message_id'] = '';
        $seqData['created_at'] = $time;
        $seqData['updated_at'] = $time;
        // update content middle的conversation id 为receive方自己的
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
            // chatmessageinseq表middlenotstoragespecific的messagedetail
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
        // 按 $direction 对messageconductsort
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
        // 由ateditmessage可能more改messagetype，thereforeif $messageEntity not为null，优先use $messageEntity 的messagetype
        if ($messageEntity !== null) {
            $messageType = $messageEntity->getContent()->getMessageTypeEnum();
        } else {
            $messageType = $seqEntity->getSeqType();
        }
        $messageTypeName = $messageType->getName();
        $messageStatus = $seqEntity->getStatus()?->getStatusName();
        // 为了section约storagenullbetween,控制message的specificcontentstorageinseqEntitymiddle,chatmessage的specificcontentstorageinmessageEntitymiddle
        if ($messageType instanceof ControlMessageType) {
            // if是控制message,message的specificcontentfromseqEntitymiddleget
            $messageData = $seqEntity->getContent()->toArray();
        } else {
            // if是chatmessage,message的specificcontentfrommessageEntitymiddleget
            $messageData = $messageEntity?->getContent()->toArray();
        }
        // chatstatistics未读人数
        $receiveList = $seqEntity->getReceiveList();
        $unreadCount = $receiveList === null ? 0 : count($receiveList->getUnreadList());
        if (empty($messageData)) {
            $messageData = [];
        }
        $carbon = Carbon::parse($seqEntity->getCreatedAt());
        $messageTopicId = (string) $seqEntity->getExtra()?->getTopicId();
        // generate客户端message结构
        $clientMessageData = [
            // service端generate的message唯一id，all局唯一。useatwithdraw、editmessage。
            'delightful_message_id' => $seqEntity->getDelightfulMessageId(),
            // 客户端generate，needios/安卓/web三端共同确定一generate算法。useat告知客户端，delightful_message_id的由来
            'app_message_id' => $seqEntity->getAppMessageId(),
            // send者
            'sender_id' => (string) $messageEntity?->getSenderId(),
            'topic_id' => $messageTopicId,
            // message的小category。控制message的小category：已读回执；withdraw；edit；入群/退群；organization架构变动; 。 展示message：text,voice,img,file,videoetc
            'type' => $messageTypeName,
            // 回显未读人数,ifuserpoint击了detail,againrequestspecific的messagecontent
            'unread_count' => $unreadCount,
            // messagesendtime，与 delightful_message_id 一起，useatwithdraw、editmessageo clock的唯一property校验。
            'send_time' => $carbon->getTimestamp(),
            // chatmessagestatus:unread | seen | read |revoked  .对应middle文释义：未读|已读|已查看（non纯文本的复杂typemessage，userpoint击了detail）  | withdraw
            'status' => $messageStatus ?: '',
            'content' => $messageData,
        ];
        $clientSeqMessage = new ClientMessage($clientMessageData);

        // generate客户端seq结构
        $clientSequenceData = [
            // 序columnnumber归属账numberid
            'delightful_id' => $seqEntity->getObjectId(),
            // 序columnnumber，一定not重复，一定growth，but是not保证连续。
            'seq_id' => $seqEntity->getSeqId(),
            // user的messageid，userdown唯一。
            'message_id' => $seqEntity->getMessageId(),
            // 本itemmessagefingerto的delightful_message_id。 useatimplement已读回执场景。存inquote关系o clock，send_msg_idfieldnotagainreturn，因为send方的messageidnothave改变。
            'refer_message_id' => $seqEntity->getReferMessageId(),
            // send方的messageid
            'sender_message_id' => $seqEntity->getSenderMessageId(),
            // message所属conversation窗口。 客户端canaccording to此value确定messagewhether要reminderetc。if本groundnothavehair现这conversationid，主动toservice端queryconversation窗口detail
            'conversation_id' => $seqEntity->getConversationId(),
            // 本itemmessage所属organization
            'organization_code' => $seqEntity->getOrganizationCode(),
            'message' => $clientSeqMessage,
            // editmessage的option
            MessageOptionsEnum::EDIT_MESSAGE_OPTIONS->value => $seqEntity->getExtra()?->getEditMessageOptions(),
        ];
        return new ClientSequence($clientSequenceData);
    }
}
