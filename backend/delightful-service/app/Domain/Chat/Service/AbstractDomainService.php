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
     * willhairitem方messagedelivertomqmiddle,useatback续按messageprioritylevel,deliverto收item方messagestreammiddle.
     */
    public function dispatchSeq(SeqCreatedEvent $seqCreatedEvent): void
    {
        // decreaseresponsedelay,尽fastgivecustomer端returnresponse.
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
        // section约storagenullbetween,chatmessageinseqtablenot存specificcontent,only存messageid
        if ($messageEntity->getMessageType() instanceof ControlMessageType) {
            $content = $messageEntity->getContent()->toArray();
        } else {
            $content = [];
        }
        return $content;
    }

    /**
     * notify收item方havenewmessage(收item方maybeisfrom己,or者ischatobject).
     * @todo considerto seqIds merge同categoryitem,decreasepushcount,subtract轻network/mq/service器stress
     */
    public function pushControlSequence(DelightfulSeqEntity $seqEntity): SeqCreatedEvent
    {
        $seqCreatedEvent = $this->getControlSeqCreatedEvent($seqEntity);
        // delivermessage
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
     * batchquantitypushmessage.
     * will多 seq_id mergeforoneitem mq messageconductpush
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
     * batchquantityminutehairmessage:improveperformance,merge多 seq_id foroneitemmessage,decreasemessagepushcount.
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
     * generatehairitem方controlmessage序column.(controlisnonchatmessage).
     * byat存in序columnnumbermerge/deletescenario,所bynotneedguarantee序columnnumbercontinuousproperty.
     */
    public function generateSenderSequenceByControlMessage(DelightfulMessageEntity $messageDTO, string $conversationId = ''): DelightfulSeqEntity
    {
        $time = date('Y-m-d H:i:s');
        // section约storagenullbetween,chatmessageinseqtablenot存specificcontent,only存messageid
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
            'delightful_message_id' => '', // controlmessagenotcanhave delightful_message_id
            'message_id' => $seqId,
            'refer_message_id' => '',
            'sender_message_id' => '',
            'conversation_id' => $conversationId,
            'status' => DelightfulMessageStatus::Read->value, // send方from己message,defaultalready读
            'created_at' => $time,
            'updated_at' => $time,
            'app_message_id' => $messageDTO->getAppMessageId(),
        ];
        return $this->delightfulSeqRepository->createSequence($seqData);
    }

    /**
     * generatehairitem方controlmessage序column.(notiscontrolchatmessage).
     * byat存in序columnnumbermerge/deletescenario,所bynotneedguarantee序columnnumbercontinuousproperty.
     */
    public function generateReceiveSequenceByControlMessage(DelightfulMessageEntity $messageDTO, DelightfulConversationEntity $receiveConversationEntity): DelightfulSeqEntity
    {
        $time = date('Y-m-d H:i:s');
        // get收item方conversation实body
        $receiveUserEntity = $this->delightfulUserRepository->getUserById($receiveConversationEntity->getUserId());
        if ($receiveUserEntity === null) {
            ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
        }
        // section约storagenullbetween,chatmessageinseqtablenot存specificcontent,only存messageid
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
            'status' => DelightfulMessageStatus::Read->value, // controlmessagenotneedalready读return执
            'created_at' => $time,
            'updated_at' => $time,
            'app_message_id' => $messageDTO->getAppMessageId(),
        ];
        return $this->delightfulSeqRepository->createSequence($seqData);
    }

    /**
     * systemstablepropertyguarantee modepiece之one:messageprioritylevelcertain
     * prioritylevelrule:
     * 1.private chat/100personbyinsidegroup chat,prioritylevelmosthigh
     * 2.systemapplicationmessage,highprioritylevel
     * 3.apimessage(thethree方callgenerate)/100~1000persongroup chat,middleprioritylevel
     * 4.controlmessage/1000personbyupgroup chat,mostlowprioritylevel.
     * 5.部minutecontrolmessageandchatstrong相close,canprioritylevel提tohigh. such asconversationwindowcreate.
     */
    public function getControlMessagePriority(DelightfulSeqEntity $seqEntity, ?int $receiveUserCount = 1): MessagePriority
    {
        $messagePriority = MessagePriority::Low;
        // 部minutecontrolmessageandchatstrong相close,canprioritylevel提tohigh. such asprivate chatandperson数less than100already读return执
        $seqType = $seqEntity->getSeqType();
        if (! in_array($seqType, ControlMessageType::getMessageStatusChangeType(), true)) {
            return $messagePriority;
        }
        $conversationEntity = $this->delightfulConversationRepository->getConversationById($seqEntity->getConversationId());
        if (! isset($conversationEntity)) {
            return $messagePriority;
        }

        if (in_array($conversationEntity->getReceiveType(), [ConversationType::User, ConversationType::Ai], true)) {
            // private chatmessagealready读return执,prioritylevelmosthigh
            $messagePriority = MessagePriority::High;
        } elseif ($receiveUserCount <= 100 && $seqEntity->getSeqType() === ControlMessageType::SeenMessages) {
            // 100personbyinsidegroup chat,prioritylevelmosthigh
            $messagePriority = MessagePriority::High;
        }
        return $messagePriority;
    }

    /**
     * customer端 already读/alreadyview/withdraw/editmessage.
     * @throws Throwable
     */
    public function clientOperateMessageStatus(DelightfulMessageEntity $messageDTO, DataIsolation $dataIsolation): array
    {
        $messageType = $messageDTO->getMessageType();
        $batchResponse = [];
        // eachitemmessagehairouto clock,thenwillinmessagebodymiddlerecord所havereceive方,by便back续messagestatus变more
        switch ($messageType) {
            case ControlMessageType::SeenMessages:
                /** @var MessagesSeen $messageStruct */
                $messageStruct = $messageDTO->getContent();
                $referMessageIds = $messageStruct->getReferMessageIds();
                // geteachitemmessagefinalstatus
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
                        // batchquantitygivefrom己generatestatus变moremessagestream序column
                        $this->delightfulSeqRepository->batchCreateSeq($userMessageStatusChangeSeqEntities);
                        // more改databasemiddlemessagestatus,avoidnewdevicelogino clockdisplaynot读
                        if (! empty($needUpdateStatusSeqIds)) {
                            $this->delightfulSeqRepository->batchUpdateSeqStatus($needUpdateStatusSeqIds, DelightfulMessageStatus::Seen);
                        }
                        $messagePriority = $this->getControlMessagePriority($userMessageStatusChangeSeqEntities[0], count($userMessageStatusChangeSeqEntities));
                        // asyncwillgeneratemessagestreamnotifyuserotherdevice.
                        $seqIds = array_column($userMessageStatusChangeSeqEntities, 'id');
                        // batchquantityminutehairalready读message,givemessagesend者
                        $this->batchDispatchSeq($seqIds, $messagePriority, $userMessageStatusChangeSeqEntities[0]->getConversationId());
                        Db::commit();
                        $this->logger->info(sprintf('batchDispatchSeq success seqIds:%s  $messagePriority:%s', Json::encode($seqIds), Json::encode($messagePriority)));
                    } catch (Throwable $exception) {
                        Db::rollBack();
                        throw $exception;
                    }
                    // batchquantitypushgivefrom己otherdevice,letotherdevicedisplayalready读,notagainduplicatesendreturn执
                    $this->batchPushSeq($seqIds, $messagePriority);
                }

                // poweretc,get refer_message_ids 实o clockstatus,ando clockresponsecustomer端
                // geteachitemmessagefinalstatus
                $messageStatusSeqEntities = $this->getReceiveMessageLatestReadStatus($referMessageIds, $dataIsolation);
                foreach ($messageStatusSeqEntities as $userSeqEntity) {
                    // format化responsestructure
                    $batchResponse[] = SeqAssembler::getClientSeqStruct($userSeqEntity, $messageDTO)->toArray();
                }
                break;
            case ControlMessageType::ReadMessage:
                // ifmessagesend者notispersoncategory,notusehandle
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
                // andhairlock
                $mutexLockKey = 'chat:revoke_message:' . $messageStruct->getReferMessageId();
                $this->redisLocker->mutexLock($mutexLockKey, $messageStruct->getReferMessageId());
                try {
                    // onlycanwithdrawfrom己hairoutmessage
                    $userSeqEntity = $this->delightfulSeqRepository->getSeqByMessageId($messageStruct->getReferMessageId());
                    if ($userSeqEntity === null || $userSeqEntity->getObjectId() !== $userEntity->getDelightfulId()) {
                        ExceptionBuilder::throw(ChatErrorCode::MESSAGE_NOT_FOUND);
                    }
                    // querymessagewhetheralreadybewithdraw
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
                            // modifyoriginal seq,markalreadywithdraw
                            $this->delightfulSeqRepository->batchUpdateSeqStatus([$userSeqEntity->getId()], DelightfulMessageStatus::Revoked);
                            // batchquantitygivefrom己generatestatus变moremessagestream序column
                            $this->delightfulSeqRepository->batchCreateSeq([$userRevokedSeqEntity]);
                            $messagePriority = $this->getControlMessagePriority($userRevokedSeqEntity);
                            // more改databasemiddlemessagestatus,avoidnewdevicelogino clockdisplaynot读
                            $this->delightfulSeqRepository->batchUpdateSeqStatus([$messageStruct->getReferMessageId()], DelightfulMessageStatus::Revoked);
                            // asyncwillgeneratemessagestreamnotifyuserotherdevice.
                            $seqIds = [$userRevokedSeqEntity->getId()];
                            // batchquantityminutehairalready读message,givemessagesend者
                            $this->batchDispatchSeq($seqIds, $messagePriority, $userSeqEntity->getConversationId());
                            Db::commit();
                            $this->logger->info(sprintf('batchDispatchSeq success seqIds:%s  $messagePriority:%s', Json::encode($seqIds), Json::encode($messagePriority)));
                        } catch (Throwable $exception) {
                            Db::rollBack();
                            throw $exception;
                        }
                        // batchquantitypushgivefrom己otherdevice,letotherdevicedisplayalready读,notagainduplicatesendreturn执
                        $this->batchPushSeq($seqIds, $messagePriority);
                    }
                    // poweretc,get refer_message_ids 实o clockstatus,ando clockresponsecustomer端
                    // format化responsestructure
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
        // willthisthese seq_id mergeforoneitem mq messageconductpush/consume
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
     * avoid seq tablecarrytoo多feature,addtoo多index,thereforewilltopicmessagesingle独writeto topic_messages tablemiddle.
     */
    public function createTopicMessage(DelightfulSeqEntity $seqEntity, ?string $topicId = null): ?DelightfulTopicMessageEntity
    {
        if ($topicId === null) {
            $topicId = $seqEntity->getExtra()?->getTopicId();
        }
        if (empty($topicId)) {
            return null;
        }
        // ifiseditmessage,notwrite
        if (! empty($seqEntity->getExtra()?->getEditMessageOptions()?->getDelightfulMessageId())) {
            return null;
        }
        // checktopicwhether存in
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
     * useractivecreatetopichandle.
     * @throws Throwable
     */
    public function userCreateTopicHandler(TopicCreateMessage $messageStruct, DataIsolation $dataIsolation): DelightfulTopicEntity
    {
        Db::beginTransaction();
        try {
            $conversationId = $messageStruct->getConversationId();
            // formessagesend方createtopic
            $topicDTO = new DelightfulTopicEntity();
            $topicDTO->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
            $topicDTO->setConversationId($conversationId);
            $topicDTO->setName($messageStruct->getName());
            $topicDTO->setDescription($messageStruct->getDescription());
            $senderTopicEntity = $this->delightfulChatTopicRepository->createTopic($topicDTO);
            // formessagereceive方createtopic
            $receiveConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId($conversationId);
            if ($receiveConversationEntity === null) {
                // justaddgood友,receive方conversation id alsonotgenerate
                return $senderTopicEntity;
            }
            $receiveTopicDTO = new DelightfulTopicEntity();
            $receiveTopicDTO->setTopicId($senderTopicEntity->getTopicId());
            $receiveTopicDTO->setName($senderTopicEntity->getName());
            $receiveTopicDTO->setConversationId($receiveConversationEntity->getId());
            $receiveTopicDTO->setOrganizationCode($receiveConversationEntity->getUserOrganizationCode());
            $receiveTopicDTO->setDescription($senderTopicEntity->getDescription());
            // for收item方createonenewtopic
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
        // judgeuserisaialsoispersoncategory
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
            // according to appMsgId,givethisitemmessagecreate delightfulMsgId
            $messageDTO->setReceiveId($conversationEntity->getReceiveId());
            $messageDTO->setReceiveType($conversationEntity->getReceiveType());
            // willconversationidreturn写entercontrolmessagemiddle,便atcustomer端handle
            $content = $messageDTO->getContent()->toArray();
            $content['id'] = $conversationEntity->getId();
            $contentChange = MessageAssembler::getMessageStructByArray(
                $messageDTO->getMessageType()->getName(),
                $content
            );
            $messageDTO->setContent($contentChange);
            $messageDTO->setMessageType($contentChange->getMessageTypeEnum());
            // givefrom己messagestreamgenerate序column.
            $seqEntity = $this->generateSenderSequenceByControlMessage($messageDTO, $conversationEntity->getId());
            $seqEntity->setConversationId($conversationEntity->getId());
            // group chatneedgive群membercreateconversationwindow
            if ($conversationEntity->getReceiveType() === ConversationType::Group || $messageDTO->getReceiveType() === ConversationType::Ai) {
                // certainmessageprioritylevel
                $seqCreatedEvent = $this->getControlSeqCreatedEvent($seqEntity);
                // asyncgive收item方(other群member)generateSeqandpush
                $this->dispatchSeq($seqCreatedEvent);
            }

            if ($receiverConversationEntity) {
                // givereceive方messagestreamgenerate序column.
                $receiverSeqEntity = $this->generateReceiveSequenceByControlMessage($messageDTO, $receiverConversationEntity);
                // certainmessageprioritylevel
                $receiverSeqCreatedEvent = $this->getControlSeqCreatedEvent($receiverSeqEntity);
                // giveto方sendmessage
                $this->dispatchSeq($receiverSeqCreatedEvent);
            }
            // willmessagestreamreturngivecurrentcustomer端! butisalsoiswillasyncpushgiveuser所haveonlinecustomer端.
            $data = SeqAssembler::getClientSeqStruct($seqEntity, $messageDTO)->toArray();
            // notifyuserotherdevice,thiswithineven ifdeliverfailalsonotimpact,所by放协程within,transactionoutside.
            co(function () use ($seqEntity) {
                // asyncpushmessagegivefrom己otherdevice
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
     * judgeconversationidwhetherisfrom己.
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
        // organizationencodingwhethermatch
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
     * byatoneitemmessage,in2conversationwindowrendero clock,willgenerate2messageid,thereforeneedparseoutcome收item方can看tomessagequoteid.
     * according to delightful_message_id + object_id + object_type 找tomessage收item方refer_message_id.
     * Support for the message editing function: For multiple sequences (seqs) of the same object_id, only the one with the smallest seq_id is returned.
     */
    protected function getMinSeqListByReferMessageId(DelightfulSeqEntity $senderSeqEntity): array
    {
        // send方from己conversationwindowwithin,quotemessageid,needconvertbecome收item方messageid
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
     * getmessagemost近status.
     * @param DelightfulSeqEntity[] $seqList 多 refer_message_id 相closeseqList
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
     * return收item方多itemmessagefinalreadstatus
     * @return DelightfulSeqEntity[]
     * @todo consideruserAdeviceeditmessage,Bdevicewithdrawmessagescenario
     */
    private function getReceiveMessageLatestReadStatus(array $referMessageIds, DataIsolation $dataIsolation): array
    {
        $referSeqList = $this->delightfulSeqRepository->getReceiveMessagesStatusChange($referMessageIds, $dataIsolation->getCurrentUserId());
        // toatreceive方come说,one sender_message_id byatstatuschange,maybewillhave多itemrecord,this处needmostbackstatus
        return $this->getMessageLatestStatus($referMessageIds, $referSeqList);
    }
}
