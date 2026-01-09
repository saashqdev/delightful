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
 * handlechatmessage相关.
 */
class DelightfulChatDomainService extends AbstractDomainService
{
    /**
     * 加入room.
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
     * return最大message的倒数 n 条序列.
     * @return ClientSequenceResponse[]
     * @deprecated
     */
    public function pullMessage(DataIsolation $dataIsolation, array $params): array
    {
        // checkuser本地 seq 和service端 seq 的diff
        $seqID = (int) $params['max_seq_info']['user_local_seq_id'];
        // return最近的 N 条message
        return $this->delightfulSeqRepository->getAccountSeqListByDelightfulId($dataIsolation, $seqID, 50);
    }

    /**
     * return最大message的倒数 n 条序列.
     * @return ClientSequenceResponse[]
     */
    public function pullByPageToken(DataIsolation $dataIsolation, array $params, int $pageSize): array
    {
        // checkuser本地 seq 和service端 seq 的diff
        $seqID = (int) $params['page_token'];
        // return最近的 N 条message
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
     * return最大message的倒数 n 条序列.
     * @return ClientSequenceResponse[]
     */
    public function pullRecentMessage(DataIsolation $dataIsolation, MessagesQueryDTO $messagesQueryDTO): array
    {
        // checkuser本地 seq 和service端 seq 的diff
        $seqId = (int) $messagesQueryDTO->getPageToken();
        $pageSize = 200;
        // return最近的 N 条message
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
        // 从conversation id中parse receive方type和receive方 id
        return $this->delightfulConversationRepository->getConversationById($conversationId);
    }

    /**
     * 系统稳定性保障模块之一:message优先级的确定
     * 优先级rule:
     * 1.private chat/100人以内的group chat,优先级最高
     * 2.系统applicationmessage,高优先级
     * 3.apimessage(第三方callgenerate)/100~1000人group chat,中优先级
     * 4.控制message/1000人以上的group chat,最低优先级.
     * 5.部分控制message与chat强相关的,can把优先级提到高. such asconversation窗口的create.
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
     * 如果user给aisend了多条message,aireply时,need让user知晓aireply的是他的哪条message.
     */
    public function aiReferMessage(DelightfulSeqEntity $aiSeqDTO, bool $doNotParseReferMessageId = false): DelightfulSeqEntity
    {
        $aiReferMessageId = $aiSeqDTO->getReferMessageId();
        $aiConversationId = $aiSeqDTO->getConversationId();
        if (empty($aiReferMessageId) || empty($aiConversationId) || $doNotParseReferMessageId) {
            return $aiSeqDTO;
        }
        // 清除invalid的quotemessage
        $aiSeqDTO->setReferMessageId('');
        // 反查user与ai的conversation窗口
        $aiConversationEntity = $this->getConversationById($aiConversationId);
        if ($aiConversationEntity === null) {
            return $aiSeqDTO;
        }
        # ai reply时quotemessage的rule:
        // 1. 本次reply前,user连续发了2条及以上的message
        // 2. 算上本次reply.ai连续发了2条及以上的message
        $conversationMessagesQueryDTO = new MessagesQueryDTO();
        $conversationMessagesQueryDTO->setConversationId($aiConversationEntity->getId())->setLimit(2)->setTopicId($aiSeqDTO->getExtra()?->getTopicId());
        $messages = $this->getConversationChatMessages($aiConversationEntity->getId(), $conversationMessagesQueryDTO);
        $userSendCount = 0;
        $aiSendCount = 1;
        // message是conversation窗口展示的倒序
        foreach ($messages as $message) {
            $senderMessageId = $message->getSeq()->getSenderMessageId();
            if (! empty($senderMessageId)) {// 对方send的
                ++$userSendCount;
                $aiSendCount = max(0, $aiSendCount - 1);
            }

            if (empty($senderMessageId)) {// ai自己send的
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
     * notify收件方有新message(收件方可能是自己,或者是chatobject).
     * @todo 考虑对 seqIds merge同类项,减少pushcount,减轻网络/mq/service器stress
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
     * generate收件方的message序列.
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
        // need按收件人的身份去queryconversation窗口id
        $receiveConversationDTO = new DelightfulConversationEntity();
        $receiveConversationDTO->setUserId($messageEntity->getReceiveId());
        $receiveConversationDTO->setUserOrganizationCode($messageEntity->getReceiveOrganizationCode());
        $receiveConversationDTO->setReceiveId($messageEntity->getSenderId());
        $receiveConversationDTO->setReceiveType($messageEntity->getSenderType());
        $receiveConversationDTO->setReceiveOrganizationCode($messageEntity->getSenderOrganizationCode());

        $receiveConversationEntity = $this->delightfulConversationRepository->getConversationByUserIdAndReceiveId($receiveConversationDTO);
        if ($receiveConversationEntity === null) {
            // 自动为收件人createconversation窗口,但不用触发收件人的窗口openevent
            $receiveConversationEntity = $this->delightfulConversationRepository->addConversation($receiveConversationDTO);
        }
        // 如果收件方已经隐藏了这个conversation窗口，改为正常
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
        // 由于一条message,在2个conversation窗口渲染时,willgenerate2个messageid,因此needparse出来收件方能看到的messagequote的id.
        $minSeqListByReferMessageId = $this->getMinSeqListByReferMessageId($senderSeqEntity);
        $receiverReferMessageId = $minSeqListByReferMessageId[$receiveUserEntity->getDelightfulId()] ?? '';
        $seqId = (string) IdGenerator::getSnowId();
        // 节约storagenull间,chatmessage在seq表不存具体content,只存messageid
        $content = $this->getSeqContent($messageEntity);
        $receiveAccountId = $this->getAccountId($messageEntity->getReceiveId());
        // according tosend方的 extra,generatereceive方对应的 extra
        $extra = $this->handlerReceiveExtra($senderSeqEntity, $receiveConversationEntity);
        $seqData = [
            'id' => $seqId,
            'organization_code' => $messageEntity->getReceiveOrganizationCode(),
            'object_type' => $messageEntity->getReceiveType()->value,
            'object_id' => $receiveAccountId,
            'seq_id' => $seqId,
            'seq_type' => $messageEntity->getMessageType()->getName(),
            // 收件方的content不needrecord未读/已读/已查看列表
            'content' => $content,
            'receive_list' => '',
            'delightful_message_id' => $messageEntity->getDelightfulMessageId(),
            'message_id' => $seqId,
            'refer_message_id' => $receiverReferMessageId,
            'sender_message_id' => $senderSeqEntity->getMessageId(), // 判断控制messagetype,如果是已读/withdraw/edit/quote,needparse出来quote的id
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
     * 由于存在序列号merge/delete的场景,所以不need保证序列号的连续性.
     */
    public function generateSenderSequenceByChatMessage(DelightfulSeqEntity $seqDTO, DelightfulMessageEntity $messageEntity, ?DelightfulConversationEntity $conversationEntity): DelightfulSeqEntity
    {
        if (empty($messageEntity->getDelightfulMessageId())) {
            ExceptionBuilder::throw(ChatErrorCode::INPUT_PARAM_ERROR);
        }
        $time = date('Y-m-d H:i:s');
        $conversationId = $conversationEntity === null ? '' : $conversationEntity->getId();
        // 节约storagenull间,chatmessage在seq表不存具体content,只存messageid
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
            // chatmessage的seq只record未读/已读/已查看列表
            'content' => $content,
            // receive人列表
            'receive_list' => $receiveList->toArray(),
            'delightful_message_id' => $messageEntity->getDelightfulMessageId(),
            'message_id' => $seqId,
            'refer_message_id' => $seqDTO->getReferMessageId(), // 判断控制messagetype,如果是已读/withdraw/edit/quote,needparse出来quote的id
            'sender_message_id' => '', // 判断控制messagetype,如果是已读/withdraw/edit/quote,needparse出来quote的id
            'conversation_id' => $conversationId,
            'status' => DelightfulMessageStatus::Read, // 自己send的message,不need判断阅读status
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
            // getconversation窗口的所有message. 有话题 + 没有话题
            return $this->delightfulSeqRepository->getConversationChatMessages($messagesQueryDTO);
        }
        if ($messagesQueryDTO->getTopicId() === '') {
            // todo get本conversation窗口中,不contain任何话题的message.
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
        // todo get本conversation窗口中,不contain任何话题的message.
        return $this->delightfulSeqRepository->getConversationsChatMessages($messagesQueryDTO, $messagesQueryDTO->getConversationIds());
    }

    /**
     * 按conversation id groupget几条最新message.
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
        // 将time转为time戳
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
     * 分发群conversationcreatemessage.
     * group chat场景,批量generatemessage序列号.
     * 由于存在序列号merge/delete的场景,所以不need保证序列号的连续性.
     * @return DelightfulSeqEntity[]
     * @throws Throwable
     */
    public function generateGroupReceiveSequence(
        DelightfulSeqEntity $senderSeqEntity,
        DelightfulMessageEntity $messageEntity,
        DelightfulMessageStatus $seqStatus = DelightfulMessageStatus::Unread
    ): array {
        if (! $senderSeqEntity->getSeqType() instanceof ChatMessageType) {
            $this->logger->error(sprintf('messageDispatch 分发group chatmessagefail,原因:非chatmessage senderSeqEntity:%s', Json::encode($senderSeqEntity->toArray())));
            return [];
        }
        // according toconversationidquery一下群info
        $conversationEntity = $this->delightfulConversationRepository->getConversationById($senderSeqEntity->getConversationId());
        if ($conversationEntity === null || $conversationEntity->getReceiveType() !== ConversationType::Group) {
            $this->logger->error(sprintf(
                'messageDispatch conversation为null或者不是group chat $senderSeqEntity:%s $conversationEntity:%s',
                Json::encode($senderSeqEntity->toArray()),
                Json::encode($conversationEntity?->toArray() ?? [])
            ));
            return [];
        }
        $groupId = $conversationEntity->getReceiveId();
        $groupEntity = $this->delightfulGroupRepository->getGroupInfoById($groupId);
        if ($groupEntity === null) {
            $this->logger->error(sprintf(
                'messageDispatch  group为null $senderSeqEntity:%s $groupEntity:%s',
                Json::encode($senderSeqEntity->toArray()),
                Json::encode($senderSeqEntity->toArray())
            ));
            return [];
        }
        try {
            Db::beginTransaction();
            // get 除send者以外的 所有群member. (因为send者的 seq 已经另外generate,单独push)
            $groupUsers = $this->delightfulGroupRepository->getGroupUserList($groupId, '');
            $groupUsers = array_column($groupUsers, null, 'user_id');
            $senderUserId = $messageEntity->getSenderId();
            unset($groupUsers[$senderUserId]);
            // getmember的delightful_id
            $userIds = array_keys($groupUsers);
            $users = $this->delightfulUserRepository->getUserByIds($userIds);
            $users = array_column($users, null, 'user_id');
            // 批量get群member的conversationinfo
            $groupUserConversations = $this->delightfulConversationRepository->batchGetConversations($userIds, $groupEntity->getId(), ConversationType::Group);
            $groupUserConversations = array_column($groupUserConversations, null, 'user_id');
            // 找到被隐藏的conversation，更改status
            $this->handlerGroupReceiverConversation($groupUserConversations);
            // 这条message是否有quote其他message
            $minSeqListByReferMessageId = $this->getMinSeqListByReferMessageId($senderSeqEntity);
            // 给这些群member批量generatechatmessage的 seq. 对于万人群,should每批一千条seq.
            $seqListCreateDTO = [];
            foreach ($groupUsers as $groupUser) {
                $user = $users[$groupUser['user_id']] ?? null;
                if (empty($groupUser['user_id']) || empty($users[$groupUser['user_id']]) || empty($user['delightful_id'])) {
                    $this->logger->error(sprintf(
                        'messageDispatch handlerConversationCreated 群member没有匹配到 $groupUser:%s $users:%s seq:%s',
                        Json::encode($groupUser),
                        Json::encode($users),
                        Json::encode($senderSeqEntity->toArray())
                    ));
                    continue;
                }

                $receiveUserConversationEntity = $groupUserConversations[$groupUser['user_id']] ?? null;
                if (empty($receiveUserConversationEntity)) {
                    $this->logger->error(sprintf(
                        'messageDispatch handlerConversationCreated 群member的conversation不存在 $groupUser:%s $users:%s seq:%s userConversation:%s',
                        Json::encode($groupUser),
                        Json::encode($users),
                        Json::encode($senderSeqEntity->toArray()),
                        Json::encode($receiveUserConversationEntity)
                    ));
                    continue;
                }
                // 多个parameter都放在DTO里handle
                $receiveSeqDTO = clone $senderSeqEntity;
                $receiveSeqDTO->setReferMessageId($minSeqListByReferMessageId[$user['delightful_id']] ?? '');
                // according to发件方的 seq,为group chat的每个membergenerate seq
                $seqEntity = $this->generateGroupSeqEntityByChatSeq(
                    $user,
                    $receiveUserConversationEntity,
                    $receiveSeqDTO,
                    $messageEntity,
                    $seqStatus
                );
                $seqListCreateDTO[$seqEntity->getId()] = $seqEntity;
            }
            # 批量generate seq
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
     * according to已经存在的chat相关 seqEntity,给群membergenerateconversation窗口.
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
        // 节约storagenull间,chatmessage在seq表不存具体content,只存messageid
        // according tosend方的 extra,generatereceive方对应的 extra
        $extra = $this->handlerReceiveExtra($receiveSeqDTO, $receiveUserConversationEntity);
        $seqData = [
            'id' => $seqId,
            'organization_code' => $userEntity['organization_code'],
            'object_type' => $userEntity['user_type'],
            'object_id' => $userEntity['delightful_id'],
            'seq_id' => $seqId,
            'seq_type' => $receiveSeqDTO->getSeqType()->value,
            // 收件方的content不needrecord未读/已读/已查看列表
            'content' => $content,
            'receive_list' => '',
            'delightful_message_id' => $messageEntity->getDelightfulMessageId(),
            'message_id' => $seqId,
            'refer_message_id' => $receiveSeqDTO->getReferMessageId(),
            'sender_message_id' => $receiveSeqDTO->getMessageId(), // 判断控制messagetype,如果是已读/withdraw/edit/quote,needparse出来quote的id
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
     * 给AIassistant用的method，contain了filteraicardmessage的逻辑.
     */
    public function getLLMContentForAgent(string $conversationId, string $topicId): array
    {
        $conversationEntity = $this->getConversationById($conversationId);
        if ($conversationEntity === null) {
            return [];
        }
        $userEntity = $this->getUserInfo($conversationEntity->getUserId());
        // 确定自己sendmessage的roletype. 只有当自己是 ai 时，自己send的message才是 assistant。（两个 ai 互相conversation暂不考虑）
        if ($userEntity->getUserType() === UserType::Ai) {
            $selfSendMessageRoleType = 'assistant';
            $otherSendMessageRoleType = 'user';
        } else {
            $selfSendMessageRoleType = 'user';
            $otherSendMessageRoleType = 'assistant';
        }
        // group装大model的messagerequest
        $messagesQueryDTO = new MessagesQueryDTO();
        $messagesQueryDTO->setConversationId($conversationId)->setLimit(200)->setTopicId($topicId);
        // get话题的最近 20 条conversationrecord
        $clientSeqResponseDTOS = $this->getConversationChatMessages($conversationId, $messagesQueryDTO);

        $userMessages = [];
        foreach ($clientSeqResponseDTOS as $clientSeqResponseDTO) {
            // 确定message的roletype
            if (empty($clientSeqResponseDTO->getSeq()->getSenderMessageId())) {
                $roleType = $selfSendMessageRoleType;
            } else {
                $roleType = $otherSendMessageRoleType;
            }
            $message = $clientSeqResponseDTO->getSeq()->getMessage()->getContent();
            // 暂时只resolvehandleuser的input，以及能get纯文本的messagetype
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
        // according to seq_id 升序排列
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
                // 只对新user产生了少量脏data
                $deleteCount += $this->delightfulSeqRepository->deleteSeqMessageByIds(array_column($sequences, 'id'));
            }
        }
        return ['$deleteCount' => $deleteCount];
    }

    /**
     * 1.need先call createAndSendStreamStartSequence create一个 seq ，然后再call streamSendJsonMessage sendmessage.
     * 2.streamsendJsonmessage,每次update json 的某个fieldmessage。
     * 3.use本机内存进行messagecache，提升大 json 读写performance。
     * @todo 如果要对外提供stream api，need改为 redis cache，以support断线重连。
     *
     *  support一次push多个field的streammessage，如果 json 层级较深，use field_1.*.field_2 作为 key。 其中 * 是指array的下标。
     *  service端willcache所有stream的data，并在streamend时一次性push，以减少丢package的概率，提升message完整性。
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
        // 自旋lock,避免data竞争。另外还need一个scheduletask扫描 redis ，对于timeout的streammessage，updatedatabase。
        $lockKey = 'delightful_stream_message:' . $appMessageId;
        $lockOwner = random_bytes(16);
        $this->locker->spinLock($lockKey, $lockOwner);
        try {
            $cachedStreamMessageKey = $this->getStreamMessageCacheKey($appMessageId);
            // handle appMessageId，避免 appMessageId 为null
            $jsonStreamCachedData = $this->getCacheStreamData($cachedStreamMessageKey);
            if ($appMessageId === '' || $jsonStreamCachedData === null || empty($jsonStreamCachedData->getSenderMessageId()) || empty($jsonStreamCachedData->getReceiveMessageId())) {
                ExceptionBuilder::throw(ChatErrorCode::STREAM_MESSAGE_NOT_FOUND);
            }

            if ($streamMessageStatus === StreamMessageStatus::Completed) {
                $streamContent = $jsonStreamCachedData->getContent();
                // updatestatus为已complete
                $streamContent['stream_options']['status'] = StreamMessageStatus::Completed->value;
                $this->updateDatabaseMessageContent($jsonStreamCachedData->getDelightfulMessageId(), $streamContent);
                $this->memoryDriver->delete($cachedStreamMessageKey);
                // 如果是endstatus，直接push全量record
                co(function () use ($jsonStreamCachedData, $streamContent) {
                    $receiveData = SeqAssembler::getClientJsonStreamSeqStruct($jsonStreamCachedData->getReceiveMessageId(), $streamContent)?->toArray(true);
                    $receiveData && $this->socketIO->of(ChatSocketIoNameSpace::Im->value)
                        ->to($jsonStreamCachedData->getReceiveDelightfulId())
                        ->compress(true)
                        ->emit(SocketEventType::Stream->value, $receiveData);
                });
            } else {
                # default就是正在stream中
                // 如果距离上次落library超过 3 秒，本次updatedatabase
                $newJsonStreamCachedDTO = (new JsonStreamCachedDTO());
                $lastUpdateDatabaseTime = $jsonStreamCachedData->getLastUpdateDatabaseTime() ?? 0;
                if (time() - $lastUpdateDatabaseTime >= 3) {
                    $needUpdateDatabase = true;
                    $newJsonStreamCachedDTO->setLastUpdateDatabaseTime(time());
                } else {
                    $needUpdateDatabase = false;
                }

                $newJsonStreamCachedDTO->setContent($thisTimeStreamMessages);
                // mergecache与本次新增的content
                $this->updateCacheStreamData($cachedStreamMessageKey, $newJsonStreamCachedDTO);

                if ($needUpdateDatabase) {
                    // 省点事，减少datamerge，只把之前的data落library
                    $this->updateDatabaseMessageContent($jsonStreamCachedData->getDelightfulMessageId(), $jsonStreamCachedData->getContent());
                }
                // 准备WebSocketpushdata并send
                $receiveData = SeqAssembler::getClientJsonStreamSeqStruct($jsonStreamCachedData->getReceiveMessageId(), $thisTimeStreamMessages)?->toArray(true);
                // pushmessage给receive方
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
        // 防止并发editmessage
        $lockKey = 'delightful_message:' . $messageEntity->getDelightfulMessageId();
        $lockOwner = random_bytes(16);
        $this->locker->mutexLock($lockKey, $lockOwner, 10);
        try {
            // editmessage时，不createnew messageEntity，而是update原message.delightfulMessageId 不变
            $oldMessageEntity = $this->getMessageByDelightfulMessageId($messageEntity->getDelightfulMessageId());
            if ($oldMessageEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::MESSAGE_NOT_FOUND);
            }
            Db::beginTransaction();
            try {
                // 如果这是message的firstversion，need把以前的message copy 一份到 message_version 表中，方便审计
                if (empty($oldMessageEntity->getCurrentVersionId())) {
                    $messageVersionEntity = (new DelightfulMessageVersionEntity())
                        ->setDelightfulMessageId($oldMessageEntity->getDelightfulMessageId())
                        ->setMessageType($oldMessageEntity->getMessageType()->value)
                        ->setMessageContent(Json::encode($oldMessageEntity->getContent()->toArray()));
                    // 先把firstversion的message存入 message_version 表
                    $this->delightfulChatMessageVersionsRepository->createMessageVersion($messageVersionEntity);
                    // 初次edit时，update收发双发的messageinitial seq，markmessage已edit，方便前端渲染
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
                        // 这里要update收发双方的 seq 各一次，且 $seqExtra value可能different，循环中update 2 次databaseshould是能接受的。
                        $this->delightfulSeqRepository->updateSeqExtra((string) $seqData['id'], $seqExtra);
                    }
                }
                // writecurrentversion的message
                $messageVersionEntity = (new DelightfulMessageVersionEntity())
                    ->setDelightfulMessageId($messageEntity->getDelightfulMessageId())
                    ->setMessageType($messageEntity->getMessageType()->value)
                    ->setMessageContent(Json::encode($messageEntity->getContent()->toArray()));
                $messageVersionEntity = $this->delightfulChatMessageVersionsRepository->createMessageVersion($messageVersionEntity);
                // updatemessage的currentversion和messagecontent，便于前端渲染
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
     * 批量getconversationdetail.
     * @param array $conversationIds conversationIDarray
     * @return array<string,DelightfulConversationEntity> 以conversationID为键的conversation实体array
     */
    public function getConversationsByIds(array $conversationIds): array
    {
        if (empty($conversationIds)) {
            return [];
        }

        // 直接use现有的Repositorymethodgetconversation实体
        $conversationEntities = $this->delightfulConversationRepository->getConversationByIds($conversationIds);

        // 以conversationID为键，方便call方快速查找
        $result = [];
        foreach ($conversationEntities as $entity) {
            $result[$entity->getId()] = $entity;
        }

        return $result;
    }

    /**
     * receive客户端产生的message,,generatedelightfulMsgId
     * 可能是createconversation,edit自己nickname等的控制message.
     */
    public function createDelightfulMessageByAppClient(DelightfulMessageEntity $messageDTO, DelightfulConversationEntity $senderConversationEntity): DelightfulMessageEntity
    {
        // 由于databasedesign有issue，conversation表没有record user 的 type，因此这里needquery一遍发件方userinfo
        // todo conversation表shouldrecord user 的 type
        $senderUserEntity = $this->delightfulUserRepository->getUserById($senderConversationEntity->getUserId());
        if ($senderUserEntity === null) {
            ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
        }
        $delightfulMsgId = $messageDTO->getDelightfulMessageId();
        $delightfulMsgId = empty($delightfulMsgId) ? IdGenerator::getUniqueId32() : $delightfulMsgId;
        $time = date('Y-m-d H:i:s');
        $id = (string) IdGenerator::getSnowId();
        // 一条messagewill出现在两个人的conversation窗口里(group chat时出现在几千人的conversation窗口id里),所以直接不存了,needconversation窗口id时再according to收件人/发件人id去 delightful_user_conversation 取
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
     * create一个stream的 seq 并立即push，由于前端渲染占位。注意，streammessage不能用来update已经push完毕的message，避免篡改originalcontent！
     * 如果need对已经发出的message进行update，needuse editMessage method，editmessagewillrecord完整的messagehistoryversion。
     */
    public function createAndSendStreamStartSequence(CreateStreamSeqDTO $createStreamSeqDTO, MessageInterface $messageStruct, DelightfulConversationEntity $senderConversationEntity): DelightfulSeqEntity
    {
        Db::beginTransaction();
        try {
            // check是否supportstreampush的messagetype
            if (! $messageStruct instanceof StreamMessageInterface || $messageStruct->getStreamOptions() === null) {
                ExceptionBuilder::throw(ChatErrorCode::STREAM_MESSAGE_NOT_FOUND);
            }
            // 由于databasedesign有issue，conversation表没有record user 的 type，因此这里needquery一遍发件方userinfo
            // todo conversation表shouldrecord user 的 type
            $senderUserEntity = $this->delightfulUserRepository->getUserById($senderConversationEntity->getUserId());
            if ($senderUserEntity === null) {
                ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
            }
            // streamstart时，在streamoption里record stream_app_message_id 给前端use
            /** @var StreamOptions $streamOptions */
            $streamOptions = $messageStruct->getStreamOptions();
            $streamOptions->setStreamAppMessageId($createStreamSeqDTO->getAppMessageId());
            $time = date('Y-m-d H:i:s');
            $language = di(TranslatorInterface::class)->getLocale();
            // 一条messagewill出现在两个人的conversation窗口里(group chat时出现在几千人的conversation窗口id里),所以直接不存了,needconversation窗口id时再according to收件人/发件人id去 delightful_user_conversation 取
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
            // 给自己的messagestreamgenerate序列,并确定message的receive人列表
            $senderSeqDTO = (new DelightfulSeqEntity())
                ->setAppMessageId($createStreamSeqDTO->getAppMessageId())
                ->setExtra((new SeqExtra())->setTopicId($createStreamSeqDTO->getTopicId()));
            $senderSeqEntity = $this->generateSenderSequenceByChatMessage($senderSeqDTO, $messageEntity, $senderConversationEntity);
            // 立即给收件方generate seq
            $receiveSeqEntity = $this->generateReceiveSequenceByChatMessage($senderSeqEntity, $messageEntity);
            // 发件方的话题message
            $this->createTopicMessage($senderSeqEntity);
            // 收件方的话题message
            $this->createTopicMessage($receiveSeqEntity);
            // cachestreammessage
            $cachedStreamMessageKey = $this->getStreamMessageCacheKey($createStreamSeqDTO->getAppMessageId());
            $jsonStreamCachedDTO = (new JsonStreamCachedDTO())
                ->setSenderMessageId($senderSeqEntity->getMessageId())
                ->setReceiveMessageId($receiveSeqEntity->getMessageId())
                ->setLastUpdateDatabaseTime(time())
                // 只initialize不write具体content，后续willaccording tostreammessage的status进行update
                ->setContent(['stream_options.status' => StreamMessageStatus::Start->value])
                ->setDelightfulMessageId($receiveSeqEntity->getDelightfulMessageId())
                ->setReceiveDelightfulId($receiveSeqEntity->getObjectId());
            $this->updateCacheStreamData($cachedStreamMessageKey, $jsonStreamCachedDTO);
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            throw $exception;
        }
        // 前端渲染need：如果是streamstart时，推一个普通 seq 给前端，用于渲染占位，但是 seq_id 并没有落library。
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
     * use本机内存进行messagecache，提升大 json 读写performance。
     * @todo 如果要对外提供stream api，need改为 redis cache，以support断线重连。
     *
     * content的format  for example：
     *   [
     *       'users.0.name' => 'delightful',
     *       'total' => 32,
     *   ]
     */
    private function updateCacheStreamData(string $cacheKey, JsonStreamCachedDTO $jsonStreamCachedDTO): void
    {
        // get现有cache，如果不存在则initialize为nullarray
        $memoryCache = $this->memoryDriver->get($cacheKey) ?? [];

        // ensure $memoryCache 是一个array，handle意外type
        if (! is_array($memoryCache)) {
            $this->logger->warning(sprintf('cache键 %s 的datatypeinvalid。reset为nullarray。', $cacheKey));
            $memoryCache = [];
        }

        // getDTO的完整data
        $jsonStreamCachedData = $jsonStreamCachedDTO->toArray();
        // 单独handlecontentfield
        $jsonContent = $jsonStreamCachedData['content'] ?? [];

        // initializecontentfield
        $memoryCacheContent = $memoryCache['content'] ?? [];

        foreach ($jsonContent as $key => $value) {
            // 如果value是string，取出旧value进行拼接
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
            // override或者update内存cache
            Arr::set($memoryCacheContent, $key, $value);
        }

        // 移除contentfield，避免后面重复handle
        unset($jsonStreamCachedData['content']);

        // 直接update其他所有非nullfield
        foreach ($jsonStreamCachedData as $key => $value) {
            if ($value !== null) {
                $memoryCache[$key] = $value;
            }
        }
        // updatestreamdata
        $jsonStreamCachedDTO->setContent($memoryCacheContent);
        $memoryCache['content'] = $memoryCacheContent;
        // updatecache，use更长的TTL以减少expire重建frequency
        $this->memoryDriver->set($cacheKey, $memoryCache, 600); // setting10分钟expiretime
    }

    /**
     * 批量get$cacheKey中的多个field. support嵌套field.
     */
    private function getCacheStreamData(string $cacheKey): ?JsonStreamCachedDTO
    {
        // get现有cache，如果不存在则initialize为nullarray
        $memoryCache = $this->memoryDriver->get($cacheKey) ?? [];

        // 如果cache不是array，则returnnullarray
        if (! is_array($memoryCache)) {
            $this->logger->warning(sprintf('cache键 %s 的datatypeinvalid。reset为nullarray。', $cacheKey));
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
        // 如果conversation窗口被隐藏，那么再次open
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
        // 收发双发的话题id一致,但是话题所属conversationiddifferent
        $receiveSeqExtra->setTopicId($senderTopicId);
        // 发件方所在的环境id
        $receiveSeqExtra->setDelightfulEnvId($senderSeqEntity->getExtra()?->getDelightfulEnvId());
        // 判断收件方的话题 id是否存在
        $topicDTO = new DelightfulTopicEntity();
        $topicDTO->setConversationId($receiveConversationEntity->getId());
        $topicDTO->setTopicId($senderTopicId);
        $topicDTO->setOrganizationCode($receiveConversationEntity->getUserOrganizationCode());
        $topicDTO->setName('');
        $topicDTO->setDescription('');
        $topicEntity = $this->delightfulChatTopicRepository->getTopicEntity($topicDTO);
        if ($topicEntity === null) {
            // 为收件方create话题
            $this->delightfulChatTopicRepository->createTopic($topicDTO);
        }
        return $receiveSeqExtra;
    }

    /**
     * 未读user列表.
     */
    private function getUnreadList(DelightfulConversationEntity $conversationEntity): array
    {
        $unreadList = [];
        if ($conversationEntity->getReceiveType() === ConversationType::Group) {
            $groupId = $conversationEntity->getReceiveId();
            // group chat
            $groupUserList = $this->delightfulGroupRepository->getGroupUserList($groupId, '', columns: ['user_id']);
            $groupUserList = array_column($groupUserList, null, 'user_id');
            // 排除自己
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
