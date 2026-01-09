<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Service;

use App\Application\Chat\Event\Publish\MessageDispatchPublisher;
use App\Application\Chat\Event\Publish\MessagePushPublisher;
use App\Domain\Chat\DTO\Message\ControlMessage\MessageRevoked;
use App\Domain\Chat\DTO\Message\ControlMessage\MessagesSeen;
use App\Domain\Chat\DTO\Message\ControlMessage\TopicCreateMessage;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\DelightfulTopicEntity;
use App\Domain\Chat\Entity\DelightfulTopicMessageEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use App\Domain\Chat\Entity\ValueObject\MessagePriority;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Event\Seq\SeqCreatedEvent;
use App\Domain\Chat\Repository\Facade\DelightfulChatConversationRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatFileRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatMessageVersionsRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatSeqRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulChatTopicRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulContactIdMappingRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulFriendRepositoryInterface;
use App\Domain\Chat\Repository\Facade\DelightfulMessageRepositoryInterface;
use App\Domain\Chat\Repository\Persistence\DelightfulContactIdMappingRepository;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\UserIdType;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\Contact\Repository\Facade\DelightfulAccountRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulUserIdRelationRepositoryInterface;
use App\Domain\Contact\Repository\Facade\DelightfulUserRepositoryInterface;
use App\Domain\File\Repository\Persistence\Facade\CloudFileRepositoryInterface;
use App\Domain\Flow\Repository\Facade\DelightfulFlowAIModelRepositoryInterface;
use App\Domain\Group\Repository\Facade\DelightfulGroupRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Facade\EnvironmentRepositoryInterface;
use App\Domain\OrganizationEnvironment\Repository\Facade\OrganizationsEnvironmentRepositoryInterface;
use App\Domain\Token\Repository\Facade\DelightfulTokenRepositoryInterface;
use App\ErrorCode\ChatErrorCode;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Infrastructure\Util\Locker\RedisLocker;
use App\Interfaces\Chat\Assembler\MessageAssembler;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Hyperf\Amqp\Producer;
use Hyperf\Cache\Driver\MemoryDriver;
use Hyperf\Codec\Json;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Hyperf\Snowflake\IdGeneratorInterface;
use Hyperf\SocketIOServer\SocketIO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Coroutine\co;

abstract class AbstractDomainService
{
    protected readonly MemoryDriver $memoryDriver;

    public function __construct(
        protected DelightfulUserRepositoryInterface $delightfulUserRepository,
        protected DelightfulMessageRepositoryInterface $delightfulMessageRepository,
        protected DelightfulChatSeqRepositoryInterface $delightfulSeqRepository,
        protected DelightfulAccountRepositoryInterface $delightfulAccountRepository,
        protected IdGeneratorInterface $idGenerator,
        protected SocketIO $socketIO,
        protected DelightfulChatConversationRepositoryInterface $delightfulConversationRepository,
        protected RedisLocker $redisLocker,
        protected Producer $producer,
        protected Redis $redis,
        protected DelightfulChatTopicRepositoryInterface $delightfulChatTopicRepository,
        protected DelightfulGroupRepositoryInterface $delightfulGroupRepository,
        protected DelightfulChatFileRepositoryInterface $delightfulFileRepository,
        protected LoggerInterface $logger,
        protected readonly DelightfulUserRepositoryInterface $userRepository,
        protected readonly DelightfulFriendRepositoryInterface $friendRepository,
        protected readonly DelightfulAccountRepositoryInterface $accountRepository,
        protected readonly DelightfulUserIdRelationRepositoryInterface $userIdRelationRepository,
        protected readonly DelightfulContactIdMappingRepositoryInterface $contactThirdPlatformIdMappingRepository,
        protected readonly DelightfulContactIdMappingRepository $contactIdMappingRepository,
        protected readonly OrganizationsEnvironmentRepositoryInterface $delightfulOrganizationsEnvironmentRepository,
        protected readonly DelightfulTokenRepositoryInterface $delightfulTokenRepository,
        protected readonly LockerInterface $locker,
        protected readonly EnvironmentRepositoryInterface $delightfulEnvironmentsRepository,
        protected readonly DelightfulFlowAIModelRepositoryInterface $delightfulFlowAIModelRepository,
        protected readonly CloudFileRepositoryInterface $cloudFileRepository,
        protected readonly DelightfulChatMessageVersionsRepositoryInterface $delightfulChatMessageVersionsRepository,
        protected ContainerInterface $container
    ) {
        try {
            $this->logger = $this->container->get(LoggerFactory::class)->get(get_class($this));
        } catch (Throwable) {
        }
        $this->memoryDriver = new MemoryDriver($container, [
            'prefix' => 'delightful-chat:',
            'skip_cache_results' => [null, '', []],
            // 1GB
            'size' => 1024 * 1024 * 1024,
            'throw_when_size_exceeded' => true,
        ], );
    }

    /**
     * messageminutehair模piece.
     * 将hairitem方的message投递tomqmiddle,useatback续按message优先level,投递to收item方的messagestreammiddle.
     */
    public function dispatchSeq(SeqCreatedEvent $seqCreatedEvent): void
    {
        // 降低responsedelay,尽快给客户端returnresponse.
        $controlMessageCreatedMq = new MessageDispatchPublisher($seqCreatedEvent);
        if (! $this->producer->produce($controlMessageCreatedMq)) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_SEND_FAILED);
        }
        $this->logger->info('DispatchMessage message:{message}', ['message' => Json::encode($seqCreatedEvent)]);
    }

    public function getMessageByDelightfulMessageId(string $delightfulMessageId): ?DelightfulMessageEntity
    {
        return $this->delightfulMessageRepository->getMessageByDelightfulMessageId($delightfulMessageId);
    }

    public function getSeqContent(DelightfulMessageEntity $messageEntity): array
    {
        // section约storagenullbetween,chatmessageinseq表not存specificcontent,只存messageid
        if ($messageEntity->getMessageType() instanceof ControlMessageType) {
            $content = $messageEntity->getContent()->toArray();
        } else {
            $content = [];
        }
        return $content;
    }

    /**
     * notify收item方have新message(收item方可能是自己,or者是chatobject).
     * @todo 考虑对 seqIds merge同categoryitem,减少pushcount,减轻network/mq/service器stress
     */
    public function pushControlSequence(DelightfulSeqEntity $seqEntity): SeqCreatedEvent
    {
        $seqCreatedEvent = $this->getControlSeqCreatedEvent($seqEntity);
        // 投递message
        $seqCreatedPublisher = new MessagePushPublisher($seqCreatedEvent);
        if (! $this->producer->produce($seqCreatedPublisher)) {
            $this->logger->error(sprintf(
                'pushMessage seqType:%s failed message:%s',
                Json::encode($seqCreatedEvent),
                $seqEntity->getSeqType()->getName()
            ));
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_DELIVERY_FAILED);
        }
        $this->logger->info('pushMessage message:' . Json::encode($seqCreatedEvent));
        return $seqCreatedEvent;
    }

    /**
     * 批quantitypushmessage.
     * 将多 seq_id merge为一item mq messageconductpush
     */
    public function batchPushSeq(array $seqIds, MessagePriority $messagePriority): void
    {
        $seqCreatedEvent = new SeqCreatedEvent($seqIds);
        $seqCreatedEvent->setPriority($messagePriority);
        $seqCreatedPublisher = new MessagePushPublisher($seqCreatedEvent);
        if (! $this->producer->produce($seqCreatedPublisher)) {
            $this->logger->error(sprintf('batchDispatchSeq failed seqIds:%s  message:%s', Json::encode($seqIds), Json::encode($seqCreatedEvent)));
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_DELIVERY_FAILED);
        }
    }

    /**
     * 批quantityminutehairmessage:提高performance,merge多 seq_id 为一itemmessage,减少messagepushcount.
     */
    public function batchDispatchSeq(array $seqIds, MessagePriority $messagePriority, string $conversationId): void
    {
        $seqCreatedEvent = new SeqCreatedEvent($seqIds);
        $seqCreatedEvent->setPriority($messagePriority);
        $seqCreatedEvent->setConversationId($conversationId);
        $seqCreatedPublisher = new MessageDispatchPublisher($seqCreatedEvent);
        if (! $this->producer->produce($seqCreatedPublisher)) {
            $this->logger->error(sprintf('batchDispatchSeq failed seqIds:%s  message:%s', Json::encode($seqIds), Json::encode($seqCreatedEvent)));
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_DELIVERY_FAILED);
        }
    }

    public function getControlSeqCreatedEvent(DelightfulSeqEntity $seqEntity): SeqCreatedEvent
    {
        $messagePriority = $this->getControlMessagePriority($seqEntity);
        $seqCreatedEvent = new SeqCreatedEvent([$seqEntity->getSeqId()]);
        $seqCreatedEvent->setPriority($messagePriority);
        $seqCreatedEvent->setConversationId($seqEntity->getConversationId());
        return $seqCreatedEvent;
    }

    /**
     * generatehairitem方的控制message序column.(控制isnonchatmessage).
     * 由at存in序columnnumbermerge/delete的场景,所bynotneed保证序columnnumber的连续property.
     */
    public function generateSenderSequenceByControlMessage(DelightfulMessageEntity $messageDTO, string $conversationId = ''): DelightfulSeqEntity
    {
        $time = date('Y-m-d H:i:s');
        // section约storagenullbetween,chatmessageinseq表not存specificcontent,只存messageid
        $content = $this->getSeqContent($messageDTO);
        $seqId = (string) IdGenerator::getSnowId();
        $senderAccountId = $this->getAccountId($messageDTO->getSenderId());
        $seqData = [
            'id' => $seqId,
            'organization_code' => $messageDTO->getSenderOrganizationCode(),
            'object_type' => $messageDTO->getSenderType()->value,
            'object_id' => $senderAccountId,
            'seq_id' => $seqId,
            'seq_type' => $messageDTO->getMessageType()->getName(),
            'content' => $content,
            'receive_list' => '',
            'delightful_message_id' => '', // 控制messagenot能have delightful_message_id
            'message_id' => $seqId,
            'refer_message_id' => '',
            'sender_message_id' => '',
            'conversation_id' => $conversationId,
            'status' => DelightfulMessageStatus::Read->value, // send方自己的message,default已读
            'created_at' => $time,
            'updated_at' => $time,
            'app_message_id' => $messageDTO->getAppMessageId(),
        ];
        return $this->delightfulSeqRepository->createSequence($seqData);
    }

    /**
     * generatehairitem方的控制message序column.(not是控制chatmessage).
     * 由at存in序columnnumbermerge/delete的场景,所bynotneed保证序columnnumber的连续property.
     */
    public function generateReceiveSequenceByControlMessage(DelightfulMessageEntity $messageDTO, DelightfulConversationEntity $receiveConversationEntity): DelightfulSeqEntity
    {
        $time = date('Y-m-d H:i:s');
        // get收item方的conversation实body
        $receiveUserEntity = $this->delightfulUserRepository->getUserById($receiveConversationEntity->getUserId());
        if ($receiveUserEntity === null) {
            ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
        }
        // section约storagenullbetween,chatmessageinseq表not存specificcontent,只存messageid
        $content = $this->getSeqContent($messageDTO);
        $seqId = (string) IdGenerator::getSnowId();
        $receiverAccountId = $receiveUserEntity->getDelightfulId();
        $seqData = [
            'id' => $seqId,
            'organization_code' => $receiveConversationEntity->getUserOrganizationCode(),
            'object_type' => $receiveUserEntity->getUserType()->value,
            'object_id' => $receiverAccountId,
            'seq_id' => $seqId,
            'seq_type' => $messageDTO->getMessageType()->getName(),
            'content' => $content,
            'receive_list' => '',
            'delightful_message_id' => '',
            'message_id' => $seqId,
            'refer_message_id' => '',
            'sender_message_id' => '',
            'conversation_id' => $receiveConversationEntity->getId(),
            'status' => DelightfulMessageStatus::Read->value, // 控制messagenotneed已读回执
            'created_at' => $time,
            'updated_at' => $time,
            'app_message_id' => $messageDTO->getAppMessageId(),
        ];
        return $this->delightfulSeqRepository->createSequence($seqData);
    }

    /**
     * 系统稳定property保障模piece之一:message优先level的确定
     * 优先levelrule:
     * 1.private chat/100人byinside的group chat,优先levelmost高
     * 2.系统applicationmessage,高优先level
     * 3.apimessage(the三方callgenerate)/100~1000人group chat,middle优先level
     * 4.控制message/1000人byup的group chat,most低优先level.
     * 5.部minute控制message与chat强相关的,can把优先level提to高. such asconversation窗口的create.
     */
    public function getControlMessagePriority(DelightfulSeqEntity $seqEntity, ?int $receiveUserCount = 1): MessagePriority
    {
        $messagePriority = MessagePriority::Low;
        // 部minute控制message与chat强相关的,can把优先level提to高. such asprivate chat和人数less than100的已读回执
        $seqType = $seqEntity->getSeqType();
        if (! in_array($seqType, ControlMessageType::getMessageStatusChangeType(), true)) {
            return $messagePriority;
        }
        $conversationEntity = $this->delightfulConversationRepository->getConversationById($seqEntity->getConversationId());
        if (! isset($conversationEntity)) {
            return $messagePriority;
        }

        if (in_array($conversationEntity->getReceiveType(), [ConversationType::User, ConversationType::Ai], true)) {
            // private chatmessage的已读回执,优先levelmost高
            $messagePriority = MessagePriority::High;
        } elseif ($receiveUserCount <= 100 && $seqEntity->getSeqType() === ControlMessageType::SeenMessages) {
            // 100人byinside的group chat,优先levelmost高
            $messagePriority = MessagePriority::High;
        }
        return $messagePriority;
    }

    /**
     * 客户端 已读/已查看/withdraw/editmessage.
     * @throws Throwable
     */
    public function clientOperateMessageStatus(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): array
    {
        $messageType = $messageDTO->getMessageType();
        $batchResponse = [];
        // eachitemmessagehair出o clock,thenwillinmessagebodymiddlerecord所have的receive方,by便back续的messagestatus变more
        switch ($messageType) {
            case ControlMessageType::SeenMessages:
                /** @var MessagesSeen $messageStruct */
                $messageStruct = $messageDTO->getContent();
                $referMessageIds = $messageStruct->getReferMessageIds();
                // geteachitemmessage的finalstatus
                $messageStatusSeqEntities = $this->getReceiveMessageLatestReadStatus($referMessageIds, $dataIsolation);
                $userMessageStatusChangeSeqEntities = [];
                $needUpdateStatusSeqIds = [];
                foreach ($messageStatusSeqEntities as $messageStatusSeqEntity) {
                    if ($messageStatusSeqEntity->getSeqType() instanceof ChatMessageType && $messageStatusSeqEntity->getStatus() === DelightfulMessageStatus::Unread) {
                        $userMessageStatusChangeSeqEntities[] = SeqAssembler::generateReceiveStatusChangeSeqEntity(
                            $messageStatusSeqEntity,
                            ControlMessageType::SeenMessages
                        );
                        $needUpdateStatusSeqIds[] = $messageStatusSeqEntity->getId();
                    }
                }
                if (! empty($userMessageStatusChangeSeqEntities)) {
                    Db::beginTransaction();
                    try {
                        // 批quantity给自己generatestatus变more的messagestream序column
                        $this->delightfulSeqRepository->batchCreateSeq($userMessageStatusChangeSeqEntities);
                        // more改databasemiddlemessage的status，避免新设备logino clock显示未读
                        if (! empty($needUpdateStatusSeqIds)) {
                            $this->delightfulSeqRepository->batchUpdateSeqStatus($needUpdateStatusSeqIds, DelightfulMessageStatus::Seen);
                        }
                        $messagePriority = $this->getControlMessagePriority($userMessageStatusChangeSeqEntities[0], count($userMessageStatusChangeSeqEntities));
                        // async将generate的messagestreamnotifyuser的其他设备.
                        $seqIds = array_column($userMessageStatusChangeSeqEntities, 'id');
                        // 批quantityminutehair已读message,给messagesend者
                        $this->batchDispatchSeq($seqIds, $messagePriority, $userMessageStatusChangeSeqEntities[0]->getConversationId());
                        Db::commit();
                        $this->logger->info(sprintf('batchDispatchSeq success seqIds:%s  $messagePriority:%s', Json::encode($seqIds), Json::encode($messagePriority)));
                    } catch (Throwable $exception) {
                        Db::rollBack();
                        throw $exception;
                    }
                    // 批quantitypush给自己的其他设备,让其他设备显示已读,notagain重复send回执
                    $this->batchPushSeq($seqIds, $messagePriority);
                }

                // 幂etc,get refer_message_ids 的实o clockstatus,及o clockresponse客户端
                // geteachitemmessage的finalstatus
                $messageStatusSeqEntities = $this->getReceiveMessageLatestReadStatus($referMessageIds, $dataIsolation);
                foreach ($messageStatusSeqEntities as $userSeqEntity) {
                    // format化response结构
                    $batchResponse[] = SeqAssembler::getClientSeqStruct($userSeqEntity, $messageDTO)->toArray();
                }
                break;
            case ControlMessageType::ReadMessage:
                // ifmessage的send者not是人category,notusehandle
                $messageEntity = $this->delightfulMessageRepository->getMessageByDelightfulMessageId($messageDTO->getDelightfulMessageId());
                if ($messageEntity === null || $messageEntity->getSenderType() !== ConversationType::User) {
                    return [];
                }
                break;
            case ControlMessageType::RevokeMessage:
                /** @var MessageRevoked $messageStruct */
                $messageStruct = $messageDTO->getContent();
                if (empty($messageStruct->getReferMessageId())) {
                    ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR);
                }
                $userEntity = $this->delightfulUserRepository->getUserById($dataIsolation->getCurrentUserId());
                if ($userEntity === null) {
                    ExceptionBuilder::throw(ChatErrorCode::USER_NOT_FOUND);
                }
                // 并hairlock
                $mutexLockKey = 'chat:revoke_message:' . $messageStruct->getReferMessageId();
                $this->redisLocker->mutexLock($mutexLockKey, $messageStruct->getReferMessageId());
                try {
                    // 只能withdraw自己hair出的message
                    $userSeqEntity = $this->delightfulSeqRepository->getSeqByMessageId($messageStruct->getReferMessageId());
                    if ($userSeqEntity === null || $userSeqEntity->getObjectId() !== $userEntity->getDelightfulId()) {
                        ExceptionBuilder::throw(ChatErrorCode::MESSAGE_NOT_FOUND);
                    }
                    // querymessagewhether已bewithdraw
                    $userRevokedSeqEntity = $this->delightfulSeqRepository->getMessageRevokedSeq(
                        $messageStruct->getReferMessageId(),
                        $userEntity,
                        ControlMessageType::RevokeMessage
                    );
                    if ($userRevokedSeqEntity === null) {
                        $userRevokedSeqEntity = SeqAssembler::generateReceiveStatusChangeSeqEntity(
                            $userSeqEntity,
                            ControlMessageType::RevokeMessage
                        );
                        Db::beginTransaction();
                        try {
                            // 修改original seq，mark已withdraw
                            $this->delightfulSeqRepository->batchUpdateSeqStatus([$userSeqEntity->getId()], DelightfulMessageStatus::Revoked);
                            // 批quantity给自己generatestatus变more的messagestream序column
                            $this->delightfulSeqRepository->batchCreateSeq([$userRevokedSeqEntity]);
                            $messagePriority = $this->getControlMessagePriority($userRevokedSeqEntity);
                            // more改databasemiddlemessage的status，避免新设备logino clock显示未读
                            $this->delightfulSeqRepository->batchUpdateSeqStatus([$messageStruct->getReferMessageId()], DelightfulMessageStatus::Revoked);
                            // async将generate的messagestreamnotifyuser的其他设备.
                            $seqIds = [$userRevokedSeqEntity->getId()];
                            // 批quantityminutehair已读message,给messagesend者
                            $this->batchDispatchSeq($seqIds, $messagePriority, $userSeqEntity->getConversationId());
                            Db::commit();
                            $this->logger->info(sprintf('batchDispatchSeq success seqIds:%s  $messagePriority:%s', Json::encode($seqIds), Json::encode($messagePriority)));
                        } catch (Throwable $exception) {
                            Db::rollBack();
                            throw $exception;
                        }
                        // 批quantitypush给自己的其他设备,让其他设备显示已读,notagain重复send回执
                        $this->batchPushSeq($seqIds, $messagePriority);
                    }
                    // 幂etc,get refer_message_ids 的实o clockstatus,及o clockresponse客户端
                    // format化response结构
                    $batchResponse[] = SeqAssembler::getClientSeqStruct($userRevokedSeqEntity, $messageDTO)->toArray();
                } finally {
                    // releaselock
                    $this->redisLocker->release($mutexLockKey, $messageStruct->getReferMessageId());
                }
                break;
            default:
                ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR);
        }
        return $batchResponse;
    }

    /**
     * @param DelightfulSeqEntity[] $seqListCreateDTO
     */
    public function batchPushControlSeqList(array $seqListCreateDTO): void
    {
        $userSeqEntity = $seqListCreateDTO[array_key_first($seqListCreateDTO)];
        // 将这些 seq_id merge为一item mq messageconductpush/消费
        $seqIds = [];
        foreach ($seqListCreateDTO as $seqEntity) {
            $seqIds[] = $seqEntity->getId();
        }
        $receiveUserCount = count($seqIds);
        $messagePriority = $this->getControlMessagePriority($userSeqEntity, $receiveUserCount);
        co(function () use ($seqIds, $messagePriority) {
            $this->batchPushSeq($seqIds, $messagePriority);
        });
    }

    public function getSeqEntityByMessageId(string $messageId): ?DelightfulSeqEntity
    {
        return $this->delightfulSeqRepository->getSeqByMessageId($messageId);
    }

    /**
     * 避免 seq 表承载too多feature,加too多索引,therefore将话题的message单独writeto topic_messages 表middle.
     */
    public function createTopicMessage(DelightfulSeqEntity $seqEntity, ?string $topicId = null): ?DelightfulTopicMessageEntity
    {
        if ($topicId === null) {
            $topicId = $seqEntity->getExtra()?->getTopicId();
        }
        if (empty($topicId)) {
            return null;
        }
        // if是editmessage，notwrite
        if (! empty($seqEntity->getExtra()?->getEditMessageOptions()?->getDelightfulMessageId())) {
            return null;
        }
        // check话题whether存in
        $topicDTO = new DelightfulTopicEntity();
        $topicDTO->setTopicId($topicId);
        $topicDTO->setConversationId($seqEntity->getConversationId());
        $topicEntity = $this->delightfulChatTopicRepository->getTopicEntity($topicDTO);
        if ($topicEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::TOPIC_NOT_FOUND);
        }
        $topicMessageDTO = new DelightfulTopicMessageEntity();
        $topicMessageDTO->setTopicId($topicId);
        $topicMessageDTO->setSeqId($seqEntity->getSeqId());
        $topicMessageDTO->setConversationId($seqEntity->getConversationId());
        $topicMessageDTO->setOrganizationCode($seqEntity->getOrganizationCode());
        $topicMessageDTO->setCreatedAt($seqEntity->getCreatedAt());
        $topicMessageDTO->setUpdatedAt($seqEntity->getUpdatedAt());
        $this->delightfulChatTopicRepository->createTopicMessage($topicMessageDTO);
        return $topicMessageDTO;
    }

    /**
     * user主动create了话题的handle。
     * @throws Throwable
     */
    public function userCreateTopicHandler(TopicCreateMessage $messageStruct, DataIsolation $dataIsolation): DelightfulTopicEntity
    {
        Db::beginTransaction();
        try {
            $conversationId = $messageStruct->getConversationId();
            // 为messagesend方create话题
            $topicDTO = new DelightfulTopicEntity();
            $topicDTO->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
            $topicDTO->setConversationId($conversationId);
            $topicDTO->setName($messageStruct->getName());
            $topicDTO->setDescription($messageStruct->getDescription());
            $senderTopicEntity = $this->delightfulChatTopicRepository->createTopic($topicDTO);
            // 为messagereceive方create话题
            $receiveConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($conversationId);
            if ($receiveConversationEntity === null) {
                // 刚加好友，receive方的conversation id also未generate
                return $senderTopicEntity;
            }
            $receiveTopicDTO = new DelightfulTopicEntity();
            $receiveTopicDTO->setTopicId($senderTopicEntity->getTopicId());
            $receiveTopicDTO->setName($senderTopicEntity->getName());
            $receiveTopicDTO->setConversationId($receiveConversationEntity->getId());
            $receiveTopicDTO->setOrganizationCode($receiveConversationEntity->getUserOrganizationCode());
            $receiveTopicDTO->setDescription($senderTopicEntity->getDescription());
            // 为收item方create一new话题
            $this->delightfulChatTopicRepository->createTopic($receiveTopicDTO);
            return $senderTopicEntity;
        } catch (Throwable $exception) {
            Db::rollBack();
            throw $exception;
        } finally {
            if (! isset($exception)) {
                Db::commit();
            }
        }
    }

    public function parsePrivateChatConversationReceiveType(DelightfulConversationEntity $conversationDTO): DelightfulConversationEntity
    {
        $receiveId = $conversationDTO->getReceiveId();
        $senderId = $conversationDTO->getUserId();
        $receiveIdPrefix = explode('_', $receiveId, 2)[0] ?? '';
        $receiveType = UserIdType::getCaseFromPrefix($receiveIdPrefix);
        if ($receiveType === null) {
            ExceptionBuilder::throw(UserErrorCode::RECEIVE_TYPE_ERROR);
        }
        $senderUserEntity = $this->delightfulUserRepository->getUserById($senderId);
        $receiverUserEntity = $this->delightfulUserRepository->getUserById($receiveId);
        if ($receiverUserEntity === null || $senderUserEntity === null) {
            ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
        }
        // 判断user是aialso是人category
        $accountEntity = $this->delightfulAccountRepository->getAccountInfoByDelightfulId($receiverUserEntity->getDelightfulId());
        if ($accountEntity === null) {
            ExceptionBuilder::throw(UserErrorCode::ACCOUNT_ERROR);
        }
        $userType = $accountEntity->getType();
        switch ($userType) {
            case UserType::Ai:
                if (empty($accountEntity->getAiCode())) {
                    ExceptionBuilder::throw(ChatErrorCode::AI_NOT_FOUND);
                }
                $conversationDTO->setReceiveType(ConversationType::Ai);
                break;
            case UserType::Human:
                $conversationDTO->setReceiveType(ConversationType::User);
                break;
        }
        $conversationDTO->setReceiveOrganizationCode($receiverUserEntity->getOrganizationCode());
        $conversationDTO->setUserOrganizationCode($senderUserEntity->getOrganizationCode());
        return $conversationDTO;
    }

    /**
     * @throws Throwable
     */
    public function handleCommonControlMessage(DelightfulMessageEntity $messageDTO, ?DelightfulConversationEntity $conversationEntity, ?DelightfulConversationEntity $receiverConversationEntity = null): array
    {
        if ($conversationEntity === null) {
            return [];
        }
        Db::beginTransaction();
        try {
            // according to appMsgId,给这itemmessagecreate delightfulMsgId
            $messageDTO->setReceiveId($conversationEntity->getReceiveId());
            $messageDTO->setReceiveType($conversationEntity->getReceiveType());
            // 将conversationid回写进控制messagemiddle,便at客户端handle
            $content = $messageDTO->getContent()->toArray();
            $content['id'] = $conversationEntity->getId();
            $contentChange = MessageAssembler::getMessageStructByArray(
                $messageDTO->getMessageType()->getName(),
                $content
            );
            $messageDTO->setContent($contentChange);
            $messageDTO->setMessageType($contentChange->getMessageTypeEnum());
            // 给自己的messagestreamgenerate序column.
            $seqEntity = $this->generateSenderSequenceByControlMessage($messageDTO, $conversationEntity->getId());
            $seqEntity->setConversationId($conversationEntity->getId());
            // group chatneed给群membercreateconversation窗口
            if ($conversationEntity->getReceiveType() === ConversationType::Group || $messageDTO->getReceiveType() === ConversationType::Ai) {
                // 确定message优先level
                $seqCreatedEvent = $this->getControlSeqCreatedEvent($seqEntity);
                // async给收item方(其他群member)generateSeq并push
                $this->dispatchSeq($seqCreatedEvent);
            }

            if ($receiverConversationEntity) {
                // 给receive方的messagestreamgenerate序column.
                $receiverSeqEntity = $this->generateReceiveSequenceByControlMessage($messageDTO, $receiverConversationEntity);
                // 确定message优先level
                $receiverSeqCreatedEvent = $this->getControlSeqCreatedEvent($receiverSeqEntity);
                // 给对方sendmessage
                $this->dispatchSeq($receiverSeqCreatedEvent);
            }
            // 将messagestreamreturn给current客户端! but是also是willasyncpush给user的所haveonline客户端.
            $data = SeqAssembler::getClientSeqStruct($seqEntity, $messageDTO)->toArray();
            // notifyuser的其他设备,这within即使投递failalsonot影响,所by放协程within,transactionoutside.
            co(function () use ($seqEntity) {
                // asyncpushmessage给自己的其他设备
                $this->pushControlSequence($seqEntity);
            });
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            throw $exception;
        }
        return $data;
    }

    /**
     * @param string[] $delightfulMessageIds
     * @return DelightfulMessageEntity[]
     */
    public function getMessageEntitiesByMaicMessageIds(array $delightfulMessageIds, ?array $rangMessageTypes = null): array
    {
        $messages = $this->delightfulMessageRepository->getMessages($delightfulMessageIds, $rangMessageTypes);
        $messageEntities = [];
        foreach ($messages as $message) {
            $messageEntity = MessageAssembler::getMessageEntity($message);
            $messageEntity && $messageEntities[] = $messageEntity;
        }
        return $messageEntities;
    }

    /**
     * 判断conversationidwhether是自己的.
     */
    protected function checkAndGetSelfConversation(string $conversationId, DataIsolation $dataIsolation): DelightfulConversationEntity
    {
        $senderConversation = $this->delightfulConversationRepository->getConversationById($conversationId);
        if ($senderConversation === null) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        if ($senderConversation->getUserId() !== $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // organizationencodingwhether匹配
        if ($senderConversation->getUserOrganizationCode() !== $dataIsolation->getCurrentOrganizationCode()) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        return $senderConversation;
    }

    protected function getAccountId(string $uid): string
    {
        $receiveEntity = $this->delightfulUserRepository->getUserById($uid);
        if ($receiveEntity === null) {
            ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
        }
        return $receiveEntity->getDelightfulId();
    }

    /**
     * 由at一itemmessage,in2conversation窗口渲染o clock,willgenerate2messageid,thereforeneedparse出来收item方能看to的messagequote的id.
     * according to delightful_message_id + object_id + object_type 找tomessage的收item方的refer_message_id.
     * Support for the message editing function: For multiple sequences (seqs) of the same object_id, only the one with the smallest seq_id is returned.
     */
    protected function getMinSeqListByReferMessageId(DelightfulSeqEntity $senderSeqEntity): array
    {
        // send方自己的conversation窗口within,quote的messageid,needconvertbecome收item方的messageid
        $sendReferMessageId = $senderSeqEntity->getReferMessageId();
        if (empty($sendReferMessageId)) {
            // nothavemessagequote
            return [];
        }
        $referSeqEntity = $this->delightfulSeqRepository->getSeqByMessageId($sendReferMessageId);
        if ($referSeqEntity === null) {
            return [];
        }
        // Optimized version: Group by object_id at MySQL level and return only the minimum seq_id record for each user
        $seqList = $this->delightfulSeqRepository->getMinSeqListByDelightfulMessageId($referSeqEntity->getDelightfulMessageId());

        // build referMap
        $referMap = [];
        foreach ($seqList as $seq) {
            $referMap[$seq['object_id']] = $seq['message_id'];
        }
        return $referMap;
    }

    /**
     * getmessage的most近status.
     * @param DelightfulSeqEntity[] $seqList 多 refer_message_id 的相关seqList
     * @return DelightfulSeqEntity[]
     */
    protected function getMessageLatestStatus(array $referMessageIds, array $seqList): array
    {
        $userMessagesReadStatus = [];
        $messageTypes = ControlMessageType::getMessageStatusChangeType();
        foreach ($seqList as $userSeq) {
            $seqType = $userSeq->getSeqType();
            if (in_array($seqType, $messageTypes, true) && in_array($userSeq->getReferMessageId(), $referMessageIds, true)) {
                $userMessageId = $userSeq->getReferMessageId();
            } elseif ($seqType instanceof ChatMessageType && in_array($userSeq->getMessageId(), $referMessageIds, true)) {
                $userMessageId = $userSeq->getMessageId();
            } else {
                $userMessageId = '';
            }
            if (empty($userMessageId) || isset($userMessagesReadStatus[$userMessageId])) {
                continue;
            }
            $userMessagesReadStatus[$userMessageId] = $userSeq;
        }
        return $userMessagesReadStatus;
    }

    /**
     * return收item方多itemmessagefinal的阅读status
     * @return DelightfulSeqEntity[]
     * @todo 考虑user的A设备editmessage,B设备withdrawmessage的场景
     */
    private function getReceiveMessageLatestReadStatus(array $referMessageIds, DataIsolation $dataIsolation): array
    {
        $referSeqList = $this->delightfulSeqRepository->getReceiveMessagesStatusChange($referMessageIds, $dataIsolation->getCurrentUserId());
        // 对atreceive方来说,一 sender_message_id 由atstatus变化,可能willhave多itemrecord,此处needmostback的status
        return $this->getMessageLatestStatus($referMessageIds, $referSeqList);
    }
}
