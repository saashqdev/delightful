<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Service;

use App\Application\Chat\Event\Publish\MessagePushPublisher;
use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Domain\Chat\DTO\Message\Options\EditMessageOptions;
use App\Domain\Chat\DTO\Message\StreamMessage\JsonStreamCachedDTO;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamMessageStatus;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamOptions;
use App\Domain\Chat\DTO\Message\StreamMessageInterface;
use App\Domain\Chat\DTO\Message\TextContentInterface;
use App\Domain\Chat\DTO\MessagesQueryDTO;
use App\Domain\Chat\DTO\Response\ClientSequenceResponse;
use App\Domain\Chat\DTO\Stream\CreateStreamSeqDTO;
use App\Domain\Chat\Entity\Items\ReceiveList;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulMessageVersionEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\DelightfulTopicEntity;
use App\Domain\Chat\Entity\ValueObject\ChatSocketIoNameSpace;
use App\Domain\Chat\Entity\ValueObject\ConversationStatus;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use App\Domain\Chat\Entity\ValueObject\MessagePriority;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\SocketEventType;
use App\Domain\Chat\Event\Seq\SeqCreatedEvent;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\ErrorCode\ChatErrorCode;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\SocketIO\SocketIOUtil;
use App\Interfaces\Chat\Assembler\MessageAssembler;
use App\Interfaces\Chat\Assembler\PageListAssembler;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Hyperf\Codec\Json;
use Hyperf\Collection\Arr;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\DbConnection\Db;
use Hyperf\SocketIOServer\Socket;
use Throwable;

use function Hyperf\Coroutine\co;

/**
 * handlechatmessage相close.
 */
class DelightfulChatDomainService extends AbstractDomainService
{
    /**
     * add入room.
     */
    public function joinRoom(string $accountId, Socket $socket): void
    {
        $socket->join($accountId);
        $this->logger->info(__METHOD__ . sprintf(' login accountId:%s sid:%s', $accountId, $socket->getSid()));
    }

    public function getUserInfo(string $userId): DelightfulUserEntity
    {
        $receiverInfo = $this->delightfulUserRepository->getUserById($userId);
        if ($receiverInfo === null) {
            ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
        }
        return $receiverInfo;
    }

    /**
     * returnmostbigmessage倒数 n item序column.
     * @return ClientSequenceResponse[]
     * @deprecated
     */
    public function pullMessage(DataIsolation $dataIsolation, array $params): array
    {
        // checkuser本ground seq andservice端 seq diff
        $seqID = (int) $params['max_seq_info']['user_local_seq_id'];
        // returnmost近 N itemmessage
        return $this->delightfulSeqRepository->getAccountSeqListByDelightfulId($dataIsolation, $seqID, 50);
    }

    /**
     * returnmostbigmessage倒数 n item序column.
     * @return ClientSequenceResponse[]
     */
    public function pullByPageToken(DataIsolation $dataIsolation, array $params, int $pageSize): array
    {
        // checkuser本ground seq andservice端 seq diff
        $seqID = (int) $params['page_token'];
        // returnmost近 N itemmessage
        $clientSeqList = $this->delightfulSeqRepository->getAccountSeqListByDelightfulId($dataIsolation, $seqID, $pageSize);
        $data = [];
        foreach ($clientSeqList as $clientSeq) {
            $data[$clientSeq->getSeq()->getSeqId()] = $clientSeq->toArray();
        }
        $hasMore = count($clientSeqList) === $pageSize;
        $pageToken = (string) array_key_first($data);
        return PageListAssembler::pageByElasticSearch(array_values($data), $pageToken, $hasMore);
    }

    /**
     * according to app_message_id pullmessage.
     * @return ClientSequenceResponse[]
     */
    public function pullByAppMessageId(DataIsolation $dataIsolation, string $appMessageId, string $pageToken, int $pageSize): array
    {
        $clientSeqList = $this->delightfulSeqRepository->getAccountSeqListByAppMessageId($dataIsolation, $appMessageId, $pageToken, $pageSize);
        $data = [];
        foreach ($clientSeqList as $clientSeq) {
            $data[$clientSeq->getSeq()->getSeqId()] = $clientSeq->toArray();
        }
        $hasMore = count($clientSeqList) === $pageSize;
        $pageToken = (string) array_key_first($data);
        return PageListAssembler::pageByElasticSearch(array_values($data), $pageToken, $hasMore);
    }

    /**
     * returnmostbigmessage倒数 n item序column.
     * @return ClientSequenceResponse[]
     */
    public function pullRecentMessage(DataIsolation $dataIsolation, MessagesQueryDTO $messagesQueryDTO): array
    {
        // checkuser本ground seq andservice端 seq diff
        $seqId = (int) $messagesQueryDTO->getPageToken();
        $pageSize = 200;
        // returnmost近 N itemmessage
        $clientSeqList = $this->delightfulSeqRepository->pullRecentMessage($dataIsolation, $seqId, $pageSize);
        $data = [];
        foreach ($clientSeqList as $clientSeq) {
            $data[$clientSeq->getSeq()->getSeqId()] = $clientSeq->toArray();
        }
        $pageToken = (string) array_key_first($data);
        return PageListAssembler::pageByElasticSearch(array_values($data), $pageToken);
    }

    public function getConversationById(string $conversationId): ?DelightfulConversationEntity
    {
        // fromconversation idmiddleparse receive方typeandreceive方 id
        return $this->delightfulConversationRepository->getConversationById($conversationId);
    }

    /**
     * systemstableproperty保障模piece之one:message优先levelcertain
     * 优先levelrule:
     * 1.private chat/100personbyinsidegroup chat,优先levelmosthigh
     * 2.systemapplicationmessage,high优先level
     * 3.apimessage(thethree方callgenerate)/100~1000persongroup chat,middle优先level
     * 4.controlmessage/1000personbyupgroup chat,mostlow优先level.
     * 5.部minutecontrolmessageandchatstrong相close,can优先level提tohigh. such asconversationwindowcreate.
     */
    public function getChatMessagePriority(ConversationType $conversationType, ?int $receiveUserCount = 1): MessagePriority
    {
        return match ($conversationType) {
            ConversationType::User => MessagePriority::Highest,
            ConversationType::CloudDocument, ConversationType::MultidimensionalTable => MessagePriority::High,
            ConversationType::System, ConversationType::App => MessagePriority::Medium,
            ConversationType::Group => match (true) {
                $receiveUserCount <= 100 => MessagePriority::Highest,
                $receiveUserCount <= 500 => MessagePriority::Medium,
                default => MessagePriority::Low,
            },
            default => MessagePriority::Low,
        };
    }

    /**
     * ifusergiveaisend多itemmessage,aireplyo clock,needletuser知晓aireplyishe哪itemmessage.
     */
    public function aiReferMessage(DelightfulSeqEntity $aiSeqDTO, bool $doNotParseReferMessageId = false): DelightfulSeqEntity
    {
        $aiReferMessageId = $aiSeqDTO->getReferMessageId();
        $aiConversationId = $aiSeqDTO->getConversationId();
        if (empty($aiReferMessageId) || empty($aiConversationId) || $doNotParseReferMessageId) {
            return $aiSeqDTO;
        }
        // 清exceptinvalidquotemessage
        $aiSeqDTO->setReferMessageId('');
        // 反查userandaiconversationwindow
        $aiConversationEntity = $this->getConversationById($aiConversationId);
        if ($aiConversationEntity === null) {
            return $aiSeqDTO;
        }
        # ai replyo clockquotemessagerule:
        // 1. 本timereplyfront,user连续hair2itemandbyupmessage
        // 2. 算up本timereply.ai连续hair2itemandbyupmessage
        $conversationMessagesQueryDTO = new MessagesQueryDTO();
        $conversationMessagesQueryDTO->setConversationId($aiConversationEntity->getId())->setLimit(2)->setTopicId($aiSeqDTO->getExtra()?->getTopicId());
        $messages = $this->getConversationChatMessages($aiConversationEntity->getId(), $conversationMessagesQueryDTO);
        $userSendCount = 0;
        $aiSendCount = 1;
        // messageisconversationwindowshow倒序
        foreach ($messages as $message) {
            $senderMessageId = $message->getSeq()->getSenderMessageId();
            if (! empty($senderMessageId)) {// to方send
                ++$userSendCount;
                $aiSendCount = max(0, $aiSendCount - 1);
            }

            if (empty($senderMessageId)) {// aifrom己send
                ++$aiSendCount;
            }
            if ($userSendCount >= 2 || $aiSendCount >= 2) {
                $aiSeqDTO->setReferMessageId($aiReferMessageId);
            }
        }
        return $aiSeqDTO;
    }

    public function getChatSeqCreatedEvent(ConversationType $receiveType, DelightfulSeqEntity $seqEntity, int $receiveUserCount): SeqCreatedEvent
    {
        $messagePriority = $this->getChatMessagePriority($receiveType, $receiveUserCount);
        $seqCreatedEvent = new SeqCreatedEvent([$seqEntity->getSeqId()]);
        $seqCreatedEvent->setPriority($messagePriority);
        $seqCreatedEvent->setConversationId($seqEntity->getConversationId());
        return $seqCreatedEvent;
    }

    public function getChatSeqPushEvent(ConversationType $receiveType, string $seqId, int $receiveUserCount): SeqCreatedEvent
    {
        $messagePriority = $this->getChatMessagePriority($receiveType, $receiveUserCount);
        $seqCreatedEvent = new SeqCreatedEvent([$seqId]);
        $seqCreatedEvent->setPriority($messagePriority);
        return $seqCreatedEvent;
    }

    /**
     * notify收item方havenewmessage(收item方maybeisfrom己,or者ischatobject).
     * @todo 考虑to seqIds merge同categoryitem,decreasepushcount,subtract轻network/mq/service器stress
     */
    public function pushChatSequence(SeqCreatedEvent $seqCreatedEvent): void
    {
        // 投递message
        $seqCreatedPublisher = new MessagePushPublisher($seqCreatedEvent);
        if (! $this->producer->produce($seqCreatedPublisher)) {
            // allowfail
            $this->logger->error('pushMessage failed message:' . Json::encode($seqCreatedEvent));
        }
    }

    /**
     * generate收item方message序column.
     */
    public function generateReceiveSequenceByChatMessage(
        DelightfulSeqEntity $senderSeqEntity,
        DelightfulMessageEntity $messageEntity,
        DelightfulMessageStatus $seqStatus = DelightfulMessageStatus::Unread
    ): DelightfulSeqEntity {
        if (empty($messageEntity->getDelightfulMessageId())) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR);
        }
        $time = date('Y-m-d H:i:s');
        // need按收itemperson身sharegoqueryconversationwindowid
        $receiveConversationDTO = new DelightfulConversationEntity();
        $receiveConversationDTO->setUserId($messageEntity->getReceiveId());
        $receiveConversationDTO->setUserOrganizationCode($messageEntity->getReceiveOrganizationCode());
        $receiveConversationDTO->setReceiveId($messageEntity->getSenderId());
        $receiveConversationDTO->setReceiveType($messageEntity->getSenderType());
        $receiveConversationDTO->setReceiveOrganizationCode($messageEntity->getSenderOrganizationCode());

        $receiveConversationEntity = $this->delightfulConversationRepository->getConversationByUserIdAndReceiveId($receiveConversationDTO);
        if ($receiveConversationEntity === null) {
            // from动for收itempersoncreateconversationwindow,butnotuse触hair收itempersonwindowopenevent
            $receiveConversationEntity = $this->delightfulConversationRepository->addConversation($receiveConversationDTO);
        }
        // if收item方already经hiddenthisconversationwindow，改fornormal
        if ($receiveConversationEntity->getStatus() !== ConversationStatus::Normal) {
            $this->delightfulConversationRepository->updateConversationById(
                $receiveConversationEntity->getId(),
                [
                    'status' => ConversationStatus::Normal->value,
                ]
            );
        }
        $receiveConversationId = $receiveConversationEntity->getId();
        $receiveUserEntity = $this->getUserInfo($messageEntity->getReceiveId());
        // byatoneitemmessage,in2conversationwindow渲染o clock,willgenerate2messageid,thereforeneedparseoutcome收item方can看tomessagequoteid.
        $minSeqListByReferMessageId = $this->getMinSeqListByReferMessageId($senderSeqEntity);
        $receiverReferMessageId = $minSeqListByReferMessageId[$receiveUserEntity->getDelightfulId()] ?? '';
        $seqId = (string) IdGenerator::getSnowId();
        // section约storagenullbetween,chatmessageinseq表not存specificcontent,only存messageid
        $content = $this->getSeqContent($messageEntity);
        $receiveAccountId = $this->getAccountId($messageEntity->getReceiveId());
        // according tosend方 extra,generatereceive方to应 extra
        $extra = $this->handlerReceiveExtra($senderSeqEntity, $receiveConversationEntity);
        $seqData = [
            'id' => $seqId,
            'organization_code' => $messageEntity->getReceiveOrganizationCode(),
            'object_type' => $messageEntity->getReceiveType()->value,
            'object_id' => $receiveAccountId,
            'seq_id' => $seqId,
            'seq_type' => $messageEntity->getMessageType()->getName(),
            // 收item方contentnotneedrecordnot读/already读/alreadyviewcolumn表
            'content' => $content,
            'receive_list' => '',
            'delightful_message_id' => $messageEntity->getDelightfulMessageId(),
            'message_id' => $seqId,
            'refer_message_id' => $receiverReferMessageId,
            'sender_message_id' => $senderSeqEntity->getMessageId(), // judgecontrolmessagetype,ifisalready读/withdraw/edit/quote,needparseoutcomequoteid
            'conversation_id' => $receiveConversationId,
            'status' => $seqStatus->value,
            'created_at' => $time,
            'updated_at' => $time,
            'extra' => isset($extra) ? $extra->toArray() : [],
            'app_message_id' => $messageEntity->getAppMessageId(),
        ];
        return $this->delightfulSeqRepository->createSequence($seqData);
    }

    /**
     * byat存in序columnnumbermerge/delete场景,所bynotneedguarantee序columnnumber连续property.
     */
    public function generateSenderSequenceByChatMessage(DelightfulSeqEntity $seqDTO, DelightfulMessageEntity $messageEntity, ?DelightfulConversationEntity $conversationEntity): DelightfulSeqEntity
    {
        if (empty($messageEntity->getDelightfulMessageId())) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR);
        }
        $time = date('Y-m-d H:i:s');
        $conversationId = $conversationEntity === null ? '' : $conversationEntity->getId();
        // section约storagenullbetween,chatmessageinseq表not存specificcontent,only存messageid
        $content = $this->getSeqContent($messageEntity);
        $receiveList = new ReceiveList();
        if ($conversationEntity) {
            $unreadList = $this->getUnreadList($conversationEntity);
            $receiveList->setUnreadList($unreadList);
        }
        $senderAccountId = $this->getAccountId($messageEntity->getSenderId());
        $seqId = (string) IdGenerator::getSnowId();
        $seqData = [
            'id' => $seqId,
            'organization_code' => $messageEntity->getSenderOrganizationCode(),
            'object_type' => $messageEntity->getSenderType()->value,
            'object_id' => $senderAccountId,
            'seq_id' => $seqId,
            'seq_type' => $messageEntity->getMessageType()->getName(),
            // chatmessageseqonlyrecordnot读/already读/alreadyviewcolumn表
            'content' => $content,
            // receivepersoncolumn表
            'receive_list' => $receiveList->toArray(),
            'delightful_message_id' => $messageEntity->getDelightfulMessageId(),
            'message_id' => $seqId,
            'refer_message_id' => $seqDTO->getReferMessageId(), // judgecontrolmessagetype,ifisalready读/withdraw/edit/quote,needparseoutcomequoteid
            'sender_message_id' => '', // judgecontrolmessagetype,ifisalready读/withdraw/edit/quote,needparseoutcomequoteid
            'conversation_id' => $conversationId,
            'status' => DelightfulMessageStatus::Read, // from己sendmessage,notneedjudge阅读status
            'created_at' => $time,
            'updated_at' => $time,
            'extra' => (array) $seqDTO->getExtra()?->toArray(),
            'app_message_id' => $seqDTO->getAppMessageId() ?: $messageEntity->getAppMessageId(),
            'language' => $messageEntity->getLanguage(),
        ];
        return $this->delightfulSeqRepository->createSequence($seqData);
    }

    /**
     * @return ClientSequenceResponse[]
     */
    public function getConversationChatMessages(string $conversationId, MessagesQueryDTO $messagesQueryDTO): array
    {
        if (empty($messagesQueryDTO->getConversationId())) {
            $messagesQueryDTO->setConversationId($conversationId);
        }
        $timeStart = $messagesQueryDTO->getTimeStart();
        $timeEnd = $messagesQueryDTO->getTimeEnd();
        $conversationEntity = $this->delightfulConversationRepository->getConversationById($conversationId);
        if ($conversationEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        if ($messagesQueryDTO->getLimit() > 1000) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR, 'chat.common.param_error', ['param' => 'limit']);
        }
        if (isset($timeEnd, $timeStart) && $timeEnd->lessThanOrEqualTo($timeStart)) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR, 'chat.common.param_error', ['param' => 'timeEnd']);
        }
        if ($messagesQueryDTO->getTopicId() === null) {
            // getconversationwindow所havemessage. have话题 + nothave话题
            return $this->delightfulSeqRepository->getConversationChatMessages($messagesQueryDTO);
        }
        if ($messagesQueryDTO->getTopicId() === '') {
            // todo get本conversationwindowmiddle,notcontainany话题message.
            return $this->delightfulSeqRepository->getConversationChatMessages($messagesQueryDTO);
        }
        return $this->delightfulChatTopicRepository->getTopicMessages($messagesQueryDTO);
    }

    /**
     * @return ClientSequenceResponse[]
     * @deprecated
     */
    public function getConversationsChatMessages(MessagesQueryDTO $messagesQueryDTO): array
    {
        $conversationEntities = $this->delightfulConversationRepository->getConversationByIds($messagesQueryDTO->getConversationIds());
        if (empty($conversationEntities)) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        if ($messagesQueryDTO->getLimit() > 1000) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR, 'chat.common.param_error', ['param' => 'limit']);
        }
        // todo get本conversationwindowmiddle,notcontainany话题message.
        return $this->delightfulSeqRepository->getConversationsChatMessages($messagesQueryDTO, $messagesQueryDTO->getConversationIds());
    }

    /**
     * 按conversation id groupget几itemmostnewmessage.
     * @return ClientSequenceResponse[]
     */
    public function getConversationsMessagesGroupById(MessagesQueryDTO $messagesQueryDTO): array
    {
        $conversationEntities = $this->delightfulConversationRepository->getConversationByIds($messagesQueryDTO->getConversationIds());
        if (empty($conversationEntities)) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        if ($messagesQueryDTO->getLimit() > 100) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR, 'chat.common.param_error', ['param' => 'limit']);
        }
        return $this->delightfulSeqRepository->getConversationsMessagesGroupById($messagesQueryDTO, $messagesQueryDTO->getConversationIds());
    }

    public function getTopicsByConversationId(DataIsolation $dataIsolation, string $conversationId, array $topicIds): array
    {
        $conversationEntity = $this->delightfulConversationRepository->getConversationById($conversationId);
        if ($conversationEntity === null || $conversationEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            return [];
        }
        $topicEntities = $this->delightfulChatTopicRepository->getTopicsByConversationId($conversationEntity->getId(), $topicIds);
        // willtime转fortime戳
        $topics = [];
        foreach ($topicEntities as &$topic) {
            $topic = $topic->toArray();
            $topic['id'] = (string) $topic['topic_id'];
            $topic['created_at'] = strtotime($topic['created_at']);
            $topic['updated_at'] = strtotime($topic['updated_at']);
            unset($topic['topic_id']);
            $topics[] = $topic;
        }
        return $topics;
    }

    /**
     * minutehair群conversationcreatemessage.
     * group chat场景,batchquantitygeneratemessage序columnnumber.
     * byat存in序columnnumbermerge/delete场景,所bynotneedguarantee序columnnumber连续property.
     * @return DelightfulSeqEntity[]
     * @throws Throwable
     */
    public function generateGroupReceiveSequence(
        DelightfulSeqEntity $senderSeqEntity,
        DelightfulMessageEntity $messageEntity,
        DelightfulMessageStatus $seqStatus = DelightfulMessageStatus::Unread
    ): array {
        if (! $senderSeqEntity->getSeqType() instanceof ChatMessageType) {
            $this->logger->error(sprintf('messageDispatch minutehairgroup chatmessagefail,reason:nonchatmessage senderSeqEntity:%s', Json::encode($senderSeqEntity->toArray())));
            return [];
        }
        // according toconversationidqueryonedown群info
        $conversationEntity = $this->delightfulConversationRepository->getConversationById($senderSeqEntity->getConversationId());
        if ($conversationEntity === null || $conversationEntity->getReceiveType() !== ConversationType::Group) {
            $this->logger->error(sprintf(
                'messageDispatch conversationfornullor者notisgroup chat $senderSeqEntity:%s $conversationEntity:%s',
                Json::encode($senderSeqEntity->toArray()),
                Json::encode($conversationEntity?->toArray() ?? [])
            ));
            return [];
        }
        $groupId = $conversationEntity->getReceiveId();
        $groupEntity = $this->delightfulGroupRepository->getGroupInfoById($groupId);
        if ($groupEntity === null) {
            $this->logger->error(sprintf(
                'messageDispatch  groupfornull $senderSeqEntity:%s $groupEntity:%s',
                Json::encode($senderSeqEntity->toArray()),
                Json::encode($senderSeqEntity->toArray())
            ));
            return [];
        }
        try {
            Db::beginTransaction();
            // get exceptsend者byoutside 所have群member. (因forsend者 seq already经另outsidegenerate,single独push)
            $groupUsers = $this->delightfulGroupRepository->getGroupUserList($groupId, '');
            $groupUsers = array_column($groupUsers, null, 'user_id');
            $senderUserId = $messageEntity->getSenderId();
            unset($groupUsers[$senderUserId]);
            // getmemberdelightful_id
            $userIds = array_keys($groupUsers);
            $users = $this->delightfulUserRepository->getUserByIds($userIds);
            $users = array_column($users, null, 'user_id');
            // batchquantityget群memberconversationinfo
            $groupUserConversations = $this->delightfulConversationRepository->batchGetConversations($userIds, $groupEntity->getId(), ConversationType::Group);
            $groupUserConversations = array_column($groupUserConversations, null, 'user_id');
            // 找tobehiddenconversation，more改status
            $this->handlerGroupReceiverConversation($groupUserConversations);
            // thisitemmessagewhetherhavequoteothermessage
            $minSeqListByReferMessageId = $this->getMinSeqListByReferMessageId($senderSeqEntity);
            // givethisthese群memberbatchquantitygeneratechatmessage seq. toatten thousandperson群,shouldeachbatchonethousanditemseq.
            $seqListCreateDTO = [];
            foreach ($groupUsers as $groupUser) {
                $user = $users[$groupUser['user_id']] ?? null;
                if (empty($groupUser['user_id']) || empty($users[$groupUser['user_id']]) || empty($user['delightful_id'])) {
                    $this->logger->error(sprintf(
                        'messageDispatch handlerConversationCreated 群membernothavematchto $groupUser:%s $users:%s seq:%s',
                        Json::encode($groupUser),
                        Json::encode($users),
                        Json::encode($senderSeqEntity->toArray())
                    ));
                    continue;
                }

                $receiveUserConversationEntity = $groupUserConversations[$groupUser['user_id']] ?? null;
                if (empty($receiveUserConversationEntity)) {
                    $this->logger->error(sprintf(
                        'messageDispatch handlerConversationCreated 群memberconversationnot存in $groupUser:%s $users:%s seq:%s userConversation:%s',
                        Json::encode($groupUser),
                        Json::encode($users),
                        Json::encode($senderSeqEntity->toArray()),
                        Json::encode($receiveUserConversationEntity)
                    ));
                    continue;
                }
                // 多parameterall放inDTOwithinhandle
                $receiveSeqDTO = clone $senderSeqEntity;
                $receiveSeqDTO->setReferMessageId($minSeqListByReferMessageId[$user['delightful_id']] ?? '');
                // according tohairitem方 seq,forgroup chateachmembergenerate seq
                $seqEntity = $this->generateGroupSeqEntityByChatSeq(
                    $user,
                    $receiveUserConversationEntity,
                    $receiveSeqDTO,
                    $messageEntity,
                    $seqStatus
                );
                $seqListCreateDTO[$seqEntity->getId()] = $seqEntity;
            }
            # batchquantitygenerate seq
            if (! empty($seqListCreateDTO)) {
                $seqListCreateDTO = $this->delightfulSeqRepository->batchCreateSeq($seqListCreateDTO);
            }
            Db::commit();
        } catch (Throwable$exception) {
            Db::rollBack();
            throw $exception;
        }

        return $seqListCreateDTO;
    }

    /**
     * according toalready经存inchat相close seqEntity,give群membergenerateconversationwindow.
     */
    public function generateGroupSeqEntityByChatSeq(
        array $userEntity,
        DelightfulConversationEntity $receiveUserConversationEntity,
        DelightfulSeqEntity $receiveSeqDTO,
        DelightfulMessageEntity $messageEntity,
        DelightfulMessageStatus $seqStatus = DelightfulMessageStatus::Unread,
    ): DelightfulSeqEntity {
        $time = date('Y-m-d H:i:s');
        $content = $this->getSeqContent($messageEntity);
        $seqId = (string) IdGenerator::getSnowId();
        // section约storagenullbetween,chatmessageinseq表not存specificcontent,only存messageid
        // according tosend方 extra,generatereceive方to应 extra
        $extra = $this->handlerReceiveExtra($receiveSeqDTO, $receiveUserConversationEntity);
        $seqData = [
            'id' => $seqId,
            'organization_code' => $userEntity['organization_code'],
            'object_type' => $userEntity['user_type'],
            'object_id' => $userEntity['delightful_id'],
            'seq_id' => $seqId,
            'seq_type' => $receiveSeqDTO->getSeqType()->value,
            // 收item方contentnotneedrecordnot读/already读/alreadyviewcolumn表
            'content' => $content,
            'receive_list' => '',
            'delightful_message_id' => $messageEntity->getDelightfulMessageId(),
            'message_id' => $seqId,
            'refer_message_id' => $receiveSeqDTO->getReferMessageId(),
            'sender_message_id' => $receiveSeqDTO->getMessageId(), // judgecontrolmessagetype,ifisalready读/withdraw/edit/quote,needparseoutcomequoteid
            'conversation_id' => $receiveUserConversationEntity->getId(),
            'status' => $seqStatus->value,
            'created_at' => $time,
            'updated_at' => $time,
            'extra' => isset($extra) ? $extra->toArray() : [],
            'app_message_id' => $messageEntity->getAppMessageId(),
        ];
        return SeqAssembler::getSeqEntity($seqData);
    }

    public function getMessageReceiveList(string $messageId, DataIsolation $dataIsolation): array
    {
        $seq = $this->delightfulSeqRepository->getMessageReceiveList($messageId, $dataIsolation->getCurrentDelightfulId(), ConversationType::User);
        $receiveList = $seq['receive_list'] ?? '{}';
        $receiveList = Json::decode($receiveList);
        return [
            'unseen_list' => $receiveList['unread_list'] ?? [],
            'seen_list' => $receiveList['seen_list'] ?? [],
            'read_list' => $receiveList['read_list'] ?? [],
        ];
    }

    /**
     * giveAIassistantusemethod，containfilteraicardmessage逻辑.
     */
    public function getLLMContentForAgent(string $conversationId, string $topicId): array
    {
        $conversationEntity = $this->getConversationById($conversationId);
        if ($conversationEntity === null) {
            return [];
        }
        $userEntity = $this->getUserInfo($conversationEntity->getUserId());
        // certainfrom己sendmessageroletype. onlywhenfrom己is ai o clock，from己sendmessage才is assistant。（两 ai 互相conversation暂not考虑）
        if ($userEntity->getUserType() === UserType::Ai) {
            $selfSendMessageRoleType = 'assistant';
            $otherSendMessageRoleType = 'user';
        } else {
            $selfSendMessageRoleType = 'user';
            $otherSendMessageRoleType = 'assistant';
        }
        // group装bigmodelmessagerequest
        $messagesQueryDTO = new MessagesQueryDTO();
        $messagesQueryDTO->setConversationId($conversationId)->setLimit(200)->setTopicId($topicId);
        // get话题most近 20 itemconversationrecord
        $clientSeqResponseDTOS = $this->getConversationChatMessages($conversationId, $messagesQueryDTO);

        $userMessages = [];
        foreach ($clientSeqResponseDTOS as $clientSeqResponseDTO) {
            // certainmessageroletype
            if (empty($clientSeqResponseDTO->getSeq()->getSenderMessageId())) {
                $roleType = $selfSendMessageRoleType;
            } else {
                $roleType = $otherSendMessageRoleType;
            }
            $message = $clientSeqResponseDTO->getSeq()->getMessage()->getContent();
            // 暂o clockonlyresolvehandleuserinput，byandcanget纯textmessagetype
            if ($message instanceof TextContentInterface) {
                $messageContent = $message->getTextContent();
            } else {
                continue;
            }
            $seqId = $clientSeqResponseDTO->getSeq()->getSeqId();
            $userMessages[$seqId] = ['role' => $roleType, 'content' => $messageContent];
        }
        if (empty($userMessages)) {
            return [];
        }
        // according to seq_id 升序rowcolumn
        ksort($userMessages);
        return array_values($userMessages);
    }

    public function deleteChatMessageByDelightfulMessageIds(array $delightfulMessageIds): void
    {
        $this->delightfulMessageRepository->deleteByDelightfulMessageIds($delightfulMessageIds);
    }

    public function getSeqMessageByIds(array $ids)
    {
        return $this->delightfulSeqRepository->getSeqMessageByIds($ids);
    }

    public function deleteTopicByIds(array $topicIds): void
    {
        $this->delightfulChatTopicRepository->deleteTopicByIds($topicIds);
    }

    public function deleteSeqMessageByIds(array $seqIds): void
    {
        $this->delightfulSeqRepository->deleteSeqMessageByIds($seqIds);
    }

    public function deleteTrashMessages(): array
    {
        $delightfulIds = $this->delightfulSeqRepository->getHasTrashMessageUsers();
        $delightfulIds = array_column($delightfulIds, 'object_id');
        $deleteCount = 0;
        foreach ($delightfulIds as $delightfulId) {
            $sequences = $this->delightfulSeqRepository->getSeqByDelightfulId($delightfulId, 100);
            if (count($sequences) < 100) {
                // onlytonewuserproduce少quantity脏data
                $deleteCount += $this->delightfulSeqRepository->deleteSeqMessageByIds(array_column($sequences, 'id'));
            }
        }
        return ['$deleteCount' => $deleteCount];
    }

    /**
     * 1.need先call createAndSendStreamStartSequence createone seq ，然backagaincall streamSendJsonMessage sendmessage.
     * 2.streamsendJsonmessage,eachtimeupdate json somefieldmessage。
     * 3.use本机inside存conductmessagecache，enhancebig json 读写performance。
     * @todo ifwanttooutsideprovidestream api，need改for redis cache，bysupport断line重连。
     *
     *  supportonetimepush多fieldstreammessage，if json layerlevelmore深，use field_1.*.field_2 asfor key。 itsmiddle * isfingerarraydown标。
     *  service端willcache所havestreamdata，andinstreamendo clockonetimepropertypush，bydecrease丢package概rate，enhancemessagecompleteproperty。
     *  for example：
     *  [
     *      'users.0.name' => 'delightful',
     *      'total' => 32,
     *  ]
     */
    public function streamSendJsonMessage(
        string $appMessageId,
        array $thisTimeStreamMessages,
        ?StreamMessageStatus $streamMessageStatus = null
    ): JsonStreamCachedDTO {
        // from旋lock,avoiddata竞争。另outsidealsoneedonescheduletask扫描 redis ，toattimeoutstreammessage，updatedatabase。
        $lockKey = 'delightful_stream_message:' . $appMessageId;
        $lockOwner = random_bytes(16);
        $this->locker->spinLock($lockKey, $lockOwner);
        try {
            $cachedStreamMessageKey = $this->getStreamMessageCacheKey($appMessageId);
            // handle appMessageId，avoid appMessageId fornull
            $jsonStreamCachedData = $this->getCacheStreamData($cachedStreamMessageKey);
            if ($appMessageId === '' || $jsonStreamCachedData === null || empty($jsonStreamCachedData->getSenderMessageId()) || empty($jsonStreamCachedData->getReceiveMessageId())) {
                ExceptionBuilder::throw(ChatErrorCode::STREAM_MESSAGE_NOT_FOUND);
            }

            if ($streamMessageStatus === StreamMessageStatus::Completed) {
                $streamContent = $jsonStreamCachedData->getContent();
                // updatestatusforalreadycomplete
                $streamContent['stream_options']['status'] = StreamMessageStatus::Completed->value;
                $this->updateDatabaseMessageContent($jsonStreamCachedData->getDelightfulMessageId(), $streamContent);
                $this->memoryDriver->delete($cachedStreamMessageKey);
                // ifisendstatus，直接pushallquantityrecord
                co(function () use ($jsonStreamCachedData, $streamContent) {
                    $receiveData = SeqAssembler::getClientJsonStreamSeqStruct($jsonStreamCachedData->getReceiveMessageId(), $streamContent)?->toArray(true);
                    $receiveData && $this->socketIO->of(ChatSocketIoNameSpace::Im->value)
                        ->to($jsonStreamCachedData->getReceiveDelightfulId())
                        ->compress(true)
                        ->emit(SocketEventType::Stream->value, $receiveData);
                });
            } else {
                # defaultthenisjustinstreammiddle
                // ifdistanceuptime落library超pass 3 second，本timeupdatedatabase
                $newJsonStreamCachedDTO = (new JsonStreamCachedDTO());
                $lastUpdateDatabaseTime = $jsonStreamCachedData->getLastUpdateDatabaseTime() ?? 0;
                if (time() - $lastUpdateDatabaseTime >= 3) {
                    $needUpdateDatabase = true;
                    $newJsonStreamCachedDTO->setLastUpdateDatabaseTime(time());
                } else {
                    $needUpdateDatabase = false;
                }

                $newJsonStreamCachedDTO->setContent($thisTimeStreamMessages);
                // mergecacheand本timenewcontent
                $this->updateCacheStreamData($cachedStreamMessageKey, $newJsonStreamCachedDTO);

                if ($needUpdateDatabase) {
                    // 省point事，decreasedatamerge，only之frontdata落library
                    $this->updateDatabaseMessageContent($jsonStreamCachedData->getDelightfulMessageId(), $jsonStreamCachedData->getContent());
                }
                // 准备WebSocketpushdataandsend
                $receiveData = SeqAssembler::getClientJsonStreamSeqStruct($jsonStreamCachedData->getReceiveMessageId(), $thisTimeStreamMessages)?->toArray(true);
                // pushmessagegivereceive方
                if ($receiveData) {
                    $this->socketIO->of(ChatSocketIoNameSpace::Im->value)
                        ->to($jsonStreamCachedData->getReceiveDelightfulId())
                        ->compress(true)
                        ->emit(SocketEventType::Stream->value, $receiveData);
                }
            }
        } finally {
            $this->locker->release($lockKey, $lockOwner);
        }
        return $jsonStreamCachedData;
    }

    public function editMessage(DelightfulMessageEntity $messageEntity): DelightfulMessageVersionEntity
    {
        // preventandhaireditmessage
        $lockKey = 'delightful_message:' . $messageEntity->getDelightfulMessageId();
        $lockOwner = random_bytes(16);
        $this->locker->mutexLock($lockKey, $lockOwner, 10);
        try {
            // editmessageo clock，notcreatenew messageEntity，whileisupdate原message.delightfulMessageId not变
            $oldMessageEntity = $this->getMessageByDelightfulMessageId($messageEntity->getDelightfulMessageId());
            if ($oldMessageEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::MESSAGE_NOT_FOUND);
            }
            Db::beginTransaction();
            try {
                // ifthisismessagefirstversion，needbyfrontmessage copy oneshareto message_version 表middle，方便审计
                if (empty($oldMessageEntity->getCurrentVersionId())) {
                    $messageVersionEntity = (new DelightfulMessageVersionEntity())
                        ->setDelightfulMessageId($oldMessageEntity->getDelightfulMessageId())
                        ->setMessageType($oldMessageEntity->getMessageType()->value)
                        ->setMessageContent(Json::encode($oldMessageEntity->getContent()->toArray()));
                    // 先firstversionmessage存入 message_version 表
                    $this->delightfulChatMessageVersionsRepository->createMessageVersion($messageVersionEntity);
                    // 初timeedito clock，update收hairdoublehairmessageinitial seq，markmessagealreadyedit，方便front端渲染
                    $seqList = $this->delightfulSeqRepository->getBothSeqListByDelightfulMessageId($messageEntity->getDelightfulMessageId());
                    foreach ($seqList as $seqData) {
                        $extra = $seqData['extra'] ?? null;
                        if (json_validate($extra)) {
                            $extra = Json::decode($extra);
                        } else {
                            $extra = [];
                        }
                        $seqExtra = new SeqExtra($extra);
                        $seqExtra->setEditMessageOptions(
                            (new EditMessageOptions())->setMessageVersionId(null)->setDelightfulMessageId($messageEntity->getDelightfulMessageId())
                        );
                        // thiswithinwantupdate收hairdouble方 seq eachonetime，and $seqExtra valuemaybedifferent，loopmiddleupdate 2 timedatabaseshouldiscanaccept。
                        $this->delightfulSeqRepository->updateSeqExtra((string) $seqData['id'], $seqExtra);
                    }
                }
                // writecurrentversionmessage
                $messageVersionEntity = (new DelightfulMessageVersionEntity())
                    ->setDelightfulMessageId($messageEntity->getDelightfulMessageId())
                    ->setMessageType($messageEntity->getMessageType()->value)
                    ->setMessageContent(Json::encode($messageEntity->getContent()->toArray()));
                $messageVersionEntity = $this->delightfulChatMessageVersionsRepository->createMessageVersion($messageVersionEntity);
                // updatemessagecurrentversionandmessagecontent，便atfront端渲染
                $this->delightfulMessageRepository->updateMessageContentAndVersionId($messageEntity, $messageVersionEntity);
                Db::commit();
            } catch (Throwable $exception) {
                Db::rollBack();
                throw $exception;
            }
            return $messageVersionEntity;
        } finally {
            $this->locker->release($lockKey, $lockOwner);
        }
    }

    /**
     * pass topic_id get conversation_id.
     *
     * @param string $topicId 话题ID
     * @return string conversation_id
     */
    public function getConversationIdByTopicId(string $topicId): string
    {
        $topic = $this->delightfulChatTopicRepository->getTopicByTopicId($topicId);
        if (! $topic) {
            ExceptionBuilder::throw(ChatErrorCode::TOPIC_NOT_FOUND);
        }

        return $topic->getConversationId();
    }

    /**
     * batchquantitygetconversationdetail.
     * @param array $conversationIds conversationIDarray
     * @return array<string,DelightfulConversationEntity> byconversationIDforkeyconversation实bodyarray
     */
    public function getConversationsByIds(array $conversationIds): array
    {
        if (empty($conversationIds)) {
            return [];
        }

        // 直接use现haveRepositorymethodgetconversation实body
        $conversationEntities = $this->delightfulConversationRepository->getConversationByIds($conversationIds);

        // byconversationIDforkey，方便call方fastspeedfind
        $result = [];
        foreach ($conversationEntities as $entity) {
            $result[$entity->getId()] = $entity;
        }

        return $result;
    }

    /**
     * receivecustomer端producemessage,,generatedelightfulMsgId
     * maybeiscreateconversation,editfrom己nicknameetccontrolmessage.
     */
    public function createDelightfulMessageByAppClient(DelightfulMessageEntity $messageDTO, DelightfulConversationEntity $senderConversationEntity): DelightfulMessageEntity
    {
        // byatdatabasedesignhaveissue，conversation表nothaverecord user  type，thereforethiswithinneedqueryone遍hairitem方userinfo
        // todo conversation表shouldrecord user  type
        $senderUserEntity = $this->delightfulUserRepository->getUserById($senderConversationEntity->getUserId());
        if ($senderUserEntity === null) {
            ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
        }
        $delightfulMsgId = $messageDTO->getDelightfulMessageId();
        $delightfulMsgId = empty($delightfulMsgId) ? IdGenerator::getUniqueId32() : $delightfulMsgId;
        $time = date('Y-m-d H:i:s');
        $id = (string) IdGenerator::getSnowId();
        // oneitemmessagewillout现in两personconversationwindowwithin(group chato clockout现in几thousandpersonconversationwindowidwithin),所by直接not存,needconversationwindowido clockagainaccording to收itemperson/hairitempersonidgo delightful_user_conversation 取
        $messageData = [
            'id' => $id,
            'sender_id' => $senderUserEntity->getUserId(),
            'sender_type' => $senderUserEntity->getUserType()->value,
            'sender_organization_code' => $senderUserEntity->getOrganizationCode(),
            'receive_id' => $senderConversationEntity->getReceiveId(),
            'receive_type' => $senderConversationEntity->getReceiveType()->value,
            'receive_organization_code' => $senderConversationEntity->getReceiveOrganizationCode(),
            'app_message_id' => $messageDTO->getAppMessageId(),
            'delightful_message_id' => $delightfulMsgId,
            'message_type' => $messageDTO->getMessageType()->getName(),
            'content' => Json::encode($messageDTO->getContent()->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'send_time' => $messageDTO->getSendTime() ?: $time,
            'language' => $messageDTO->getLanguage(),
            'created_at' => $time,
            'updated_at' => $time,
        ];
        $this->delightfulMessageRepository->createMessage($messageData);
        return MessageAssembler::getMessageEntity($messageData);
    }

    /**
     * createonestream seq andimmediatelypush，byatfront端渲染占位。notice，streammessagenotcanusecomeupdatealready经push完毕message，avoid篡改originalcontent！
     * ifneedtoalready经hairoutmessageconductupdate，needuse editMessage method，editmessagewillrecordcompletemessagehistoryversion。
     */
    public function createAndSendStreamStartSequence(CreateStreamSeqDTO $createStreamSeqDTO, MessageInterface $messageStruct, DelightfulConversationEntity $senderConversationEntity): DelightfulSeqEntity
    {
        Db::beginTransaction();
        try {
            // checkwhethersupportstreampushmessagetype
            if (! $messageStruct instanceof StreamMessageInterface || $messageStruct->getStreamOptions() === null) {
                ExceptionBuilder::throw(ChatErrorCode::STREAM_MESSAGE_NOT_FOUND);
            }
            // byatdatabasedesignhaveissue，conversation表nothaverecord user  type，thereforethiswithinneedqueryone遍hairitem方userinfo
            // todo conversation表shouldrecord user  type
            $senderUserEntity = $this->delightfulUserRepository->getUserById($senderConversationEntity->getUserId());
            if ($senderUserEntity === null) {
                ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
            }
            // streamstarto clock，instreamoptionwithinrecord stream_app_message_id givefront端use
            /** @var StreamOptions $streamOptions */
            $streamOptions = $messageStruct->getStreamOptions();
            $streamOptions->setStreamAppMessageId($createStreamSeqDTO->getAppMessageId());
            $time = date('Y-m-d H:i:s');
            $language = di(TranslatorInterface::class)->getLocale();
            // oneitemmessagewillout现in两personconversationwindowwithin(group chato clockout现in几thousandpersonconversationwindowidwithin),所by直接not存,needconversationwindowido clockagainaccording to收itemperson/hairitempersonidgo delightful_user_conversation 取
            $messageData = [
                'id' => (string) IdGenerator::getSnowId(),
                'sender_id' => $senderUserEntity->getUserId(),
                'sender_type' => $senderUserEntity->getUserType()->value,
                'sender_organization_code' => $senderUserEntity->getOrganizationCode(),
                'receive_id' => $senderConversationEntity->getReceiveId(),
                'receive_type' => $senderConversationEntity->getReceiveType()->value,
                'receive_organization_code' => $senderConversationEntity->getReceiveOrganizationCode(),
                'app_message_id' => $createStreamSeqDTO->getAppMessageId(),
                'delightful_message_id' => IdGenerator::getUniqueId32(),
                'message_type' => $messageStruct->getMessageTypeEnum()->getName(),
                'content' => Json::encode($messageStruct->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'language' => $language,
                'send_time' => $time,
                'created_at' => $time,
                'updated_at' => $time,
            ];
            $this->delightfulMessageRepository->createMessage($messageData);
            $messageEntity = MessageAssembler::getMessageEntity($messageData);
            if ($messageEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::STREAM_MESSAGE_NOT_FOUND);
            }
            // givefrom己messagestreamgenerate序column,andcertainmessagereceivepersoncolumn表
            $senderSeqDTO = (new DelightfulSeqEntity())
                ->setAppMessageId($createStreamSeqDTO->getAppMessageId())
                ->setExtra((new SeqExtra())->setTopicId($createStreamSeqDTO->getTopicId()));
            $senderSeqEntity = $this->generateSenderSequenceByChatMessage($senderSeqDTO, $messageEntity, $senderConversationEntity);
            // immediatelygive收item方generate seq
            $receiveSeqEntity = $this->generateReceiveSequenceByChatMessage($senderSeqEntity, $messageEntity);
            // hairitem方话题message
            $this->createTopicMessage($senderSeqEntity);
            // 收item方话题message
            $this->createTopicMessage($receiveSeqEntity);
            // cachestreammessage
            $cachedStreamMessageKey = $this->getStreamMessageCacheKey($createStreamSeqDTO->getAppMessageId());
            $jsonStreamCachedDTO = (new JsonStreamCachedDTO())
                ->setSenderMessageId($senderSeqEntity->getMessageId())
                ->setReceiveMessageId($receiveSeqEntity->getMessageId())
                ->setLastUpdateDatabaseTime(time())
                // onlyinitializenotwritespecificcontent，back续willaccording tostreammessagestatusconductupdate
                ->setContent(['stream_options.status' => StreamMessageStatus::Start->value])
                ->setDelightfulMessageId($receiveSeqEntity->getDelightfulMessageId())
                ->setReceiveDelightfulId($receiveSeqEntity->getObjectId());
            $this->updateCacheStreamData($cachedStreamMessageKey, $jsonStreamCachedDTO);
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            throw $exception;
        }
        // front端渲染need：ifisstreamstarto clock，推one普通 seq givefront端，useat渲染占位，butis seq_id andnothave落library。
        SocketIOUtil::sendSequenceId($receiveSeqEntity);
        return $senderSeqEntity;
    }

    /**
     * Check if message has already been sent by app message ID.
     *
     * @param string $appMessageId Application message ID (primary key from external table)
     * @param string $messageType Optional message type filter (empty string means no type filter)
     * @return bool True if message already exists, false otherwise
     */
    public function isMessageAlreadySent(string $appMessageId, string $messageType = ''): bool
    {
        if (empty($appMessageId)) {
            return false;
        }

        try {
            return $this->delightfulMessageRepository->isMessageExistsByAppMessageId($appMessageId, $messageType);
        } catch (Throwable $e) {
            // Log error but don't throw exception to avoid affecting main process
            $this->logger->warning(sprintf(
                'Failed to check duplicate message: %s, App Message ID: %s, Message Type: %s',
                $e->getMessage(),
                $appMessageId,
                $messageType ?: 'any'
            ));
            // Return false to allow sending when check fails
            return false;
        }
    }

    /**
     * use本机inside存conductmessagecache，enhancebig json 读写performance。
     * @todo ifwanttooutsideprovidestream api，need改for redis cache，bysupport断line重连。
     *
     * contentformat  for example：
     *   [
     *       'users.0.name' => 'delightful',
     *       'total' => 32,
     *   ]
     */
    private function updateCacheStreamData(string $cacheKey, JsonStreamCachedDTO $jsonStreamCachedDTO): void
    {
        // get现havecache，ifnot存intheninitializefornullarray
        $memoryCache = $this->memoryDriver->get($cacheKey) ?? [];

        // ensure $memoryCache isonearray，handle意outsidetype
        if (! is_array($memoryCache)) {
            $this->logger->warning(sprintf('cachekey %s datatypeinvalid。resetfornullarray。', $cacheKey));
            $memoryCache = [];
        }

        // getDTOcompletedata
        $jsonStreamCachedData = $jsonStreamCachedDTO->toArray();
        // single独handlecontentfield
        $jsonContent = $jsonStreamCachedData['content'] ?? [];

        // initializecontentfield
        $memoryCacheContent = $memoryCache['content'] ?? [];

        foreach ($jsonContent as $key => $value) {
            // ifvalueisstring，取outoldvalueconductsplice
            if (is_string($value)) {
                $value = Arr::get($memoryCacheContent, $key) . $value;
            } elseif (is_array($value)) {
                // arraymerge
                $data = [];
                if (Arr::has($memoryCacheContent, $key)) {
                    $data[] = Arr::get($memoryCacheContent, $key);
                    $data[] = $value;
                    $value = array_merge(...$data);
                }
            }
            // overrideor者updateinside存cache
            Arr::set($memoryCacheContent, $key, $value);
        }

        // 移exceptcontentfield，avoidbacksurfaceduplicatehandle
        unset($jsonStreamCachedData['content']);

        // 直接updateother所havenonnullfield
        foreach ($jsonStreamCachedData as $key => $value) {
            if ($value !== null) {
                $memoryCache[$key] = $value;
            }
        }
        // updatestreamdata
        $jsonStreamCachedDTO->setContent($memoryCacheContent);
        $memoryCache['content'] = $memoryCacheContent;
        // updatecache，usemorelongTTLbydecreaseexpire重建frequency
        $this->memoryDriver->set($cacheKey, $memoryCache, 600); // setting10minute钟expiretime
    }

    /**
     * batchquantityget$cacheKeymiddle多field. support嵌setfield.
     */
    private function getCacheStreamData(string $cacheKey): ?JsonStreamCachedDTO
    {
        // get现havecache，ifnot存intheninitializefornullarray
        $memoryCache = $this->memoryDriver->get($cacheKey) ?? [];

        // ifcachenotisarray，thenreturnnullarray
        if (! is_array($memoryCache)) {
            $this->logger->warning(sprintf('cachekey %s datatypeinvalid。resetfornullarray。', $cacheKey));
            return null;
        }
        return new JsonStreamCachedDTO($memoryCache);
    }

    private function updateDatabaseMessageContent(string $delightfulMessageId, array $messageStreamContent)
    {
        $this->delightfulMessageRepository->updateMessageContent($delightfulMessageId, $messageStreamContent);
    }

    /**
     * @param DelightfulConversationEntity[] $groupUserConversations
     */
    private function handlerGroupReceiverConversation(array $groupUserConversations): void
    {
        $needUpdateIds = [];
        // ifconversationwindowbehidden，that么againtimeopen
        foreach ($groupUserConversations as $groupUserConversation) {
            if ($groupUserConversation->getStatus() !== ConversationStatus::Normal) {
                $needUpdateIds[] = $groupUserConversation->getId();
            }
        }
        if (! empty($needUpdateIds)) {
            $this->delightfulConversationRepository->updateConversationStatusByIds($needUpdateIds, ConversationStatus::Normal);
        }
    }

    private function handlerReceiveExtra(DelightfulSeqEntity $senderSeqEntity, DelightfulConversationEntity $receiveConversationEntity): ?SeqExtra
    {
        $senderSeqExtra = $senderSeqEntity->getExtra();
        if ($senderSeqExtra === null) {
            return null;
        }
        $receiveSeqExtra = new SeqExtra();
        // handleeditmessage
        $editOptions = $senderSeqExtra->getEditMessageOptions();
        if ($editOptions !== null) {
            $receiveSeqExtra->setEditMessageOptions($editOptions);
        }
        // handle话题
        $senderTopicId = $senderSeqExtra->getTopicId();
        if (empty($senderTopicId)) {
            return $receiveSeqExtra;
        }
        // 收hairdoublehair话题idone致,butis话题所属conversationiddifferent
        $receiveSeqExtra->setTopicId($senderTopicId);
        // hairitem方所inenvironmentid
        $receiveSeqExtra->setDelightfulEnvId($senderSeqEntity->getExtra()?->getDelightfulEnvId());
        // judge收item方话题 idwhether存in
        $topicDTO = new DelightfulTopicEntity();
        $topicDTO->setConversationId($receiveConversationEntity->getId());
        $topicDTO->setTopicId($senderTopicId);
        $topicDTO->setOrganizationCode($receiveConversationEntity->getUserOrganizationCode());
        $topicDTO->setName('');
        $topicDTO->setDescription('');
        $topicEntity = $this->delightfulChatTopicRepository->getTopicEntity($topicDTO);
        if ($topicEntity === null) {
            // for收item方create话题
            $this->delightfulChatTopicRepository->createTopic($topicDTO);
        }
        return $receiveSeqExtra;
    }

    /**
     * not读usercolumn表.
     */
    private function getUnreadList(DelightfulConversationEntity $conversationEntity): array
    {
        $unreadList = [];
        if ($conversationEntity->getReceiveType() === ConversationType::Group) {
            $groupId = $conversationEntity->getReceiveId();
            // group chat
            $groupUserList = $this->delightfulGroupRepository->getGroupUserList($groupId, '', columns: ['user_id']);
            $groupUserList = array_column($groupUserList, null, 'user_id');
            // rowexceptfrom己
            unset($groupUserList[$conversationEntity->getUserId()]);
            $unreadList = array_keys($groupUserList);
        }
        if (in_array($conversationEntity->getReceiveType(), [ConversationType::User, ConversationType::Ai], true)) {
            // private chat
            $unreadList = [$conversationEntity->getReceiveId()];
        }
        return $unreadList;
    }

    private function getStreamMessageCacheKey(string $appMessageId): string
    {
        return 'cached_delightful_stream_message:' . $appMessageId;
    }
}
