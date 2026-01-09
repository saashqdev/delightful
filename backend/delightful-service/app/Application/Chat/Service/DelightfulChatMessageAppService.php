<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Application\ModelGateway\Mapper\ModelGatewayMapper;
use App\Application\ModelGateway\Service\LLMAppService;
use App\Application\ModelGateway\Service\ModelConfigAppService;
use App\Domain\Chat\DTO\ConversationListQueryDTO;
use App\Domain\Chat\DTO\Message\ChatFileInterface;
use App\Domain\Chat\DTO\Message\ChatMessage\AbstractAttachmentMessage;
use App\Domain\Chat\DTO\Message\ChatMessage\VoiceMessage;
use App\Domain\Chat\DTO\Message\MessageInterface;
use App\Domain\Chat\DTO\Message\StreamMessage\StreamMessageStatus;
use App\Domain\Chat\DTO\Message\StreamMessageInterface;
use App\Domain\Chat\DTO\Message\TextContentInterface;
use App\Domain\Chat\DTO\MessagesQueryDTO;
use App\Domain\Chat\DTO\PageResponseDTO\ConversationsPageResponseDTO;
use App\Domain\Chat\DTO\Request\ChatRequest;
use App\Domain\Chat\DTO\Request\Common\DelightfulContext;
use App\Domain\Chat\DTO\Response\ClientSequenceResponse;
use App\Domain\Chat\DTO\Stream\CreateStreamSeqDTO;
use App\Domain\Chat\DTO\UserGroupConversationQueryDTO;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulChatFileEntity;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationStatus;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Entity\ValueObject\FileType;
use App\Domain\Chat\Entity\ValueObject\LLMModelEnum;
use App\Domain\Chat\Entity\ValueObject\DelightfulMessageStatus;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Service\DelightfulChatDomainService;
use App\Domain\Chat\Service\DelightfulChatFileDomainService;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Chat\Service\DelightfulSeqDomainService;
use App\Domain\Chat\Service\DelightfulTopicDomainService;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Domain\File\Service\FileDomainService;
use App\Domain\ModelGateway\Entity\ValueObject\ModelGatewayDataIsolation;
use App\Domain\ModelGateway\Service\ModelConfigDomainService;
use App\ErrorCode\ChatErrorCode;
use App\ErrorCode\UserErrorCode;
use App\Infrastructure\Core\Constants\Order;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Infrastructure\Util\Odin\AgentFactory;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Chat\Assembler\MessageAssembler;
use App\Interfaces\Chat\Assembler\PageListAssembler;
use App\Interfaces\Chat\Assembler\SeqAssembler;
use Carbon\Carbon;
use Hyperf\Codec\Json;
use Hyperf\Context\ApplicationContext;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Odin\Memory\MessageHistory;
use Hyperf\Odin\Message\Role;
use Hyperf\Odin\Message\SystemMessage;
use Hyperf\Redis\Redis;
use Hyperf\SocketIOServer\Socket;
use Hyperf\WebSocketServer\Context as WebSocketContext;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Throwable;

use function Hyperf\Coroutine\co;

/**
 * chatmessage相close.
 */
class DelightfulChatMessageAppService extends DelightfulSeqAppService
{
    public function __construct(
        protected LoggerInterface $logger,
        protected readonly DelightfulChatDomainService $delightfulChatDomainService,
        protected readonly DelightfulTopicDomainService $delightfulTopicDomainService,
        protected readonly DelightfulConversationDomainService $delightfulConversationDomainService,
        protected readonly DelightfulChatFileDomainService $delightfulChatFileDomainService,
        protected DelightfulSeqDomainService $delightfulSeqDomainService,
        protected FileDomainService $fileDomainService,
        protected CacheInterface $cache,
        protected DelightfulUserDomainService $delightfulUserDomainService,
        protected Redis $redis,
        protected LockerInterface $locker,
        protected readonly LLMAppService $llmAppService,
        protected readonly ModelConfigDomainService $modelConfigDomainService,
        protected readonly DelightfulMessageVersionDomainService $delightfulMessageVersionDomainService,
    ) {
        try {
            $this->logger = ApplicationContext::getContainer()->get(LoggerFactory::class)?->get(get_class($this));
        } catch (Throwable) {
        }
        parent::__construct($delightfulSeqDomainService);
    }

    public function joinRoom(DelightfulUserAuthorization $userAuthorization, Socket $socket): void
    {
        // will所have sid alladd入toroom id valuefor delightfulId roommiddle
        $this->delightfulChatDomainService->joinRoom($userAuthorization->getDelightfulId(), $socket);
    }

    /**
     * returnmostbigmessagecountdown n item序column.
     * @return ClientSequenceResponse[]
     * @deprecated
     */
    public function pullMessage(DelightfulUserAuthorization $userAuthorization, array $params): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulChatDomainService->pullMessage($dataIsolation, $params);
    }

    /**
     * returnmostbigmessagecountdown n item序column.
     * @return ClientSequenceResponse[]
     */
    public function pullByPageToken(DelightfulUserAuthorization $userAuthorization, array $params): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $pageSize = 200;
        return $this->delightfulChatDomainService->pullByPageToken($dataIsolation, $params, $pageSize);
    }

    /**
     * returnmostbigmessagecountdown n item序column.
     * @return ClientSequenceResponse[]
     */
    public function pullByAppMessageId(DelightfulUserAuthorization $userAuthorization, string $appMessageId, string $pageToken): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $pageSize = 200;
        return $this->delightfulChatDomainService->pullByAppMessageId($dataIsolation, $appMessageId, $pageToken, $pageSize);
    }

    public function pullRecentMessage(DelightfulUserAuthorization $userAuthorization, MessagesQueryDTO $messagesQueryDTO): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulChatDomainService->pullRecentMessage($dataIsolation, $messagesQueryDTO);
    }

    public function getConversations(DelightfulUserAuthorization $userAuthorization, ConversationListQueryDTO $queryDTO): ConversationsPageResponseDTO
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $result = $this->delightfulConversationDomainService->getConversations($dataIsolation, $queryDTO);
        $filterAccountEntity = $this->delightfulUserDomainService->getByAiCode($dataIsolation, 'SUPER_DELIGHTFUL');
        if (! empty($filterAccountEntity) && count($result->getItems()) > 0) {
            $filterItems = [];
            foreach ($result->getItems() as $item) {
                /**
                 * @var DelightfulConversationEntity $item
                 */
                if ($item->getReceiveId() !== $filterAccountEntity->getUserId()) {
                    $filterItems[] = $item;
                }
            }
            $result->setItems($filterItems);
        }
        return $result;
    }

    public function getUserGroupConversation(UserGroupConversationQueryDTO $queryDTO): ?DelightfulConversationEntity
    {
        $conversationEntity = DelightfulConversationEntity::fromUserGroupConversationQueryDTO($queryDTO);
        return $this->delightfulConversationDomainService->getConversationByUserIdAndReceiveId($conversationEntity);
    }

    /**
     * @throws Throwable
     */
    public function onChatMessage(ChatRequest $chatRequest, DelightfulUserAuthorization $userAuthorization): array
    {
        $conversationEntity = $this->delightfulChatDomainService->getConversationById($chatRequest->getData()->getConversationId());
        if ($conversationEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        $seqDTO = new DelightfulSeqEntity();
        $seqDTO->setReferMessageId($chatRequest->getData()->getReferMessageId());
        $topicId = $chatRequest->getData()->getMessage()->getTopicId();
        $seqExtra = new SeqExtra();
        $seqExtra->setDelightfulEnvId($userAuthorization->getDelightfulEnvId());
        // whetheriseditmessage
        $editMessageOptions = $chatRequest->getData()->getEditMessageOptions();
        if ($editMessageOptions !== null) {
            $seqExtra->setEditMessageOptions($editMessageOptions);
        }
        // seq extensioninfo. ifneedretrievetopicmessage,请query topic_messages table
        $topicId && $seqExtra->setTopicId($topicId);
        $seqDTO->setExtra($seqExtra);
        // ifis跟assistantprivate chat,andnothavetopic id,from动createonetopic
        if ($conversationEntity->getReceiveType() === ConversationType::Ai && empty($seqDTO->getExtra()?->getTopicId())) {
            $topicId = $this->delightfulTopicDomainService->agentSendMessageGetTopicId($conversationEntity, 0);
            // notimpact原havelogic,will topicId settingto extra middle
            $seqExtra = $seqDTO->getExtra() ?? new SeqExtra();
            $seqExtra->setTopicId($topicId);
            $seqDTO->setExtra($seqExtra);
        }
        $senderUserEntity = $this->delightfulChatDomainService->getUserInfo($conversationEntity->getUserId());
        $messageDTO = MessageAssembler::getChatMessageDTOByRequest(
            $chatRequest,
            $conversationEntity,
            $senderUserEntity
        );
        return $this->dispatchClientChatMessage($seqDTO, $messageDTO, $userAuthorization, $conversationEntity);
    }

    /**
     * messageauthentication.
     * @throws Throwable
     */
    public function checkSendMessageAuth(DelightfulSeqEntity $senderSeqDTO, DelightfulMessageEntity $senderMessageDTO, DelightfulConversationEntity $conversationEntity, DataIsolation $dataIsolation): void
    {
        // checkconversation idbelong toorganization,andcurrentpass inorganizationencodingone致property
        if ($conversationEntity->getUserOrganizationCode() !== $dataIsolation->getCurrentOrganizationCode()) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // judgeconversationhairup者whetheriscurrentuser,andandnotisassistant
        if ($conversationEntity->getReceiveType() !== ConversationType::Ai && $conversationEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // conversationwhetheralreadybedelete
        if ($conversationEntity->getStatus() === ConversationStatus::Delete) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_DELETED);
        }
        // ifiseditmessage,checkbeeditmessagelegalproperty(from己hairmessage,andincurrentconversationmiddle)
        $this->checkEditMessageLegality($senderSeqDTO, $dataIsolation);
        return;
        // todo ifmessagemiddlehavefile:1.judgefile所have者whetheriscurrentuser;2.judgeuserwhetherreceivepassthisthesefile.
        /* @phpstan-ignore-next-line */
        $messageContent = $senderMessageDTO->getContent();
        if ($messageContent instanceof ChatFileInterface) {
            $fileIds = $messageContent->getFileIds();
            if (! empty($fileIds)) {
                // batchquantityqueryfile所have权,whilenotisloopquery
                $fileEntities = $this->delightfulChatFileDomainService->getFileEntitiesByFileIds($fileIds);

                // checkwhether所havefileall存in
                $existingFileIds = array_map(static function (DelightfulChatFileEntity $fileEntity) {
                    return $fileEntity->getFileId();
                }, $fileEntities);

                // checkwhetherhaverequestfile ID notinalreadyquerytofile ID middle
                $missingFileIds = array_diff($fileIds, $existingFileIds);
                if (! empty($missingFileIds)) {
                    ExceptionBuilder::throw(ChatErrorCode::FILE_NOT_FOUND);
                }

                // checkfile所have者whetheriscurrentuser
                foreach ($fileEntities as $fileEntity) {
                    if ($fileEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
                        ExceptionBuilder::throw(ChatErrorCode::FILE_NOT_FOUND);
                    }
                }
            }
        }

        // todo checkwhetherhavehairmessagepermission(needhavegood友close系,企业close系,collection团close系,合as伙伴close系etc)
    }

    /**
     * assistantgivepersoncategoryorgrouphairmessage,supportonlinemessageandofflinemessage(depend onatuserwhetheronline).
     * @param DelightfulSeqEntity $aiSeqDTO how to pass parameterscanreference apilayer aiSendMessage method
     * @param string $appMessageId messageprevent duplicate,customer端(includeflow)from己tomessagegenerateoneitemencoding
     * @param bool $doNotParseReferMessageId notby chat judge referMessageId quoteo clock机,bycall方from己judge
     * @throws Throwable
     */
    public function aiSendMessage(
        DelightfulSeqEntity $aiSeqDTO,
        string $appMessageId = '',
        ?Carbon $sendTime = null,
        bool $doNotParseReferMessageId = false
    ): array {
        try {
            if ($sendTime === null) {
                $sendTime = new Carbon();
            }
            // ifusergiveassistantsend多itemmessage,assistantreplyo clock,needletuserawareassistantreplyishe哪itemmessage.
            $aiSeqDTO = $this->delightfulChatDomainService->aiReferMessage($aiSeqDTO, $doNotParseReferMessageId);
            // getassistantconversationwindow
            $aiConversationEntity = $this->delightfulChatDomainService->getConversationById($aiSeqDTO->getConversationId());
            if ($aiConversationEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
            }
            // confirmhairitempersonwhetherisassistant
            $aiUserId = $aiConversationEntity->getUserId();
            $aiUserEntity = $this->delightfulChatDomainService->getUserInfo($aiUserId);
            if ($aiUserEntity->getUserType() !== UserType::Ai) {
                ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
            }
            // ifisassistantandpersonprivate chat,andassistantsendmessagenothavetopic id,thenerror
            if ($aiConversationEntity->getReceiveType() === ConversationType::User && empty($aiSeqDTO->getExtra()?->getTopicId())) {
                ExceptionBuilder::throw(ChatErrorCode::TOPIC_ID_NOT_FOUND);
            }
            // assistantpreparestarthairmessage,endinputstatus
            $contentStruct = $aiSeqDTO->getContent();
            $isStream = $contentStruct instanceof StreamMessageInterface && $contentStruct->isStream();
            $beginStreamMessage = $isStream && $contentStruct instanceof StreamMessageInterface && $contentStruct->getStreamOptions()?->getStatus() === StreamMessageStatus::Start;
            if (! $isStream || $beginStreamMessage) {
                // nonstreamresponseor者streamresponsestartinput
                $this->delightfulConversationDomainService->agentOperateConversationStatusV2(
                    ControlMessageType::EndConversationInput,
                    $aiConversationEntity->getId(),
                    $aiSeqDTO->getExtra()?->getTopicId()
                );
            }
            // createuserAuth
            $userAuthorization = $this->getAgentAuth($aiUserEntity);
            // createmessage
            $messageDTO = $this->createAgentMessageDTO($aiSeqDTO, $aiUserEntity, $aiConversationEntity, $appMessageId, $sendTime);
            return $this->dispatchClientChatMessage($aiSeqDTO, $messageDTO, $userAuthorization, $aiConversationEntity);
        } catch (Throwable $exception) {
            $this->logger->error(Json::encode([
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]));
            throw $exception;
        }
    }

    /**
     * assistantgivepersoncategoryorgrouphairmessage,cannot传conversationandtopic id,from动createconversation,nongroupconversationfrom动adapttopic id.
     * @param string $appMessageId messageprevent duplicate,customer端(includeflow)from己tomessagegenerateoneitemencoding
     * @param bool $doNotParseReferMessageId cannotby chat judge referMessageId quoteo clock机,bycall方from己judge
     * @throws Throwable
     */
    public function agentSendMessage(
        DelightfulSeqEntity $aiSeqDTO,
        string $senderUserId,
        string $receiverId,
        string $appMessageId = '',
        bool $doNotParseReferMessageId = false,// cannotby chat judge referMessageId quoteo clock机,bycall方from己judge
        ?Carbon $sendTime = null,
        ?ConversationType $receiverType = null
    ): array {
        // 1.judge $senderUserId and $receiverUserIdconversationwhether存in(referencegetOrCreateConversationmethod)
        $senderConversationEntity = $this->delightfulConversationDomainService->getOrCreateConversation($senderUserId, $receiverId, $receiverType);
        // alsowantcreatereceive方conversationwindow,wantnot然no法createtopic
        $this->delightfulConversationDomainService->getOrCreateConversation($receiverId, $senderUserId);

        // 2.if $seqExtra notfor null,validationwhetherhave topic id,ifnothave,reference agentSendMessageGetTopicId method,totopic id
        $topicId = $aiSeqDTO->getExtra()?->getTopicId() ?? '';
        if (empty($topicId) && $receiverType !== ConversationType::Group) {
            $topicId = $this->delightfulTopicDomainService->agentSendMessageGetTopicId($senderConversationEntity, 0);
        }
        // 3.group装parameter,call aiSendMessage method
        $aiSeqDTO->getExtra() === null && $aiSeqDTO->setExtra(new SeqExtra());
        $aiSeqDTO->getExtra()->setTopicId($topicId);
        $aiSeqDTO->setConversationId($senderConversationEntity->getId());
        return $this->aiSendMessage($aiSeqDTO, $appMessageId, $sendTime, $doNotParseReferMessageId);
    }

    /**
     * personcategorygiveassistantorgrouphairmessage,cannot传conversationandtopic id,from动createconversation,nongroupconversationfrom动adapttopic id.
     * @param string $appMessageId messageprevent duplicate,customer端(includeflow)from己tomessagegenerateoneitemencoding
     * @param bool $doNotParseReferMessageId cannotby chat judge referMessageId quoteo clock机,bycall方from己judge
     * @throws Throwable
     */
    public function userSendMessageToAgent(
        DelightfulSeqEntity $aiSeqDTO,
        string $senderUserId,
        string $receiverId,
        string $appMessageId = '',
        bool $doNotParseReferMessageId = false,// cannotby chat judge referMessageId quoteo clock机,bycall方from己judge
        ?Carbon $sendTime = null,
        ?ConversationType $receiverType = null,
        string $topicId = ''
    ): array {
        // 1.judge $senderUserId and $receiverUserIdconversationwhether存in(referencegetOrCreateConversationmethod)
        $senderConversationEntity = $this->delightfulConversationDomainService->getOrCreateConversation($senderUserId, $receiverId, $receiverType);
        // ifreceive方nongroup,thencreate senderUserId and receiverUserId conversation.
        if ($receiverType !== ConversationType::Group) {
            $this->delightfulConversationDomainService->getOrCreateConversation($receiverId, $senderUserId);
        }
        // 2.if $seqExtra notfor null,validationwhetherhave topic id,ifnothave,reference agentSendMessageGetTopicId method,totopic id
        if (empty($topicId)) {
            $topicId = $aiSeqDTO->getExtra()?->getTopicId() ?? '';
        }

        if (empty($topicId) && $receiverType !== ConversationType::Group) {
            $topicId = $this->delightfulTopicDomainService->agentSendMessageGetTopicId($senderConversationEntity, 0);
        }

        // ifisgroup,thennotneedgettopic id
        if ($receiverType === ConversationType::Group) {
            $topicId = '';
        }

        // 3.group装parameter,call sendMessageToAgent method
        $aiSeqDTO->getExtra() === null && $aiSeqDTO->setExtra(new SeqExtra());
        $aiSeqDTO->getExtra()->setTopicId($topicId);
        $aiSeqDTO->setConversationId($senderConversationEntity->getId());
        return $this->sendMessageToAgent($aiSeqDTO, $appMessageId, $sendTime, $doNotParseReferMessageId);
    }

    /**
     * assistantgivepersoncategoryorgrouphairmessage,supportonlinemessageandofflinemessage(depend onatuserwhetheronline).
     * @param DelightfulSeqEntity $aiSeqDTO how to pass parameterscanreference apilayer aiSendMessage method
     * @param string $appMessageId messageprevent duplicate,customer端(includeflow)from己tomessagegenerateoneitemencoding
     * @param bool $doNotParseReferMessageId notby chat judge referMessageId quoteo clock机,bycall方from己judge
     * @throws Throwable
     */
    public function sendMessageToAgent(
        DelightfulSeqEntity $aiSeqDTO,
        string $appMessageId = '',
        ?Carbon $sendTime = null,
        bool $doNotParseReferMessageId = false
    ): array {
        try {
            if ($sendTime === null) {
                $sendTime = new Carbon();
            }
            // ifusergiveassistantsend多itemmessage,assistantreplyo clock,needletuserawareassistantreplyishe哪itemmessage.
            $aiSeqDTO = $this->delightfulChatDomainService->aiReferMessage($aiSeqDTO, $doNotParseReferMessageId);
            // getassistantconversationwindow
            $aiConversationEntity = $this->delightfulChatDomainService->getConversationById($aiSeqDTO->getConversationId());
            if ($aiConversationEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
            }
            // confirmhairitempersonwhetherisassistant
            $aiUserId = $aiConversationEntity->getUserId();
            $aiUserEntity = $this->delightfulChatDomainService->getUserInfo($aiUserId);
            // if ($aiUserEntity->getUserType() !== UserType::Ai) {
            //     ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
            // }
            // ifisassistantandpersonprivate chat,andassistantsendmessagenothavetopic id,thenerror
            if ($aiConversationEntity->getReceiveType() === ConversationType::User && empty($aiSeqDTO->getExtra()?->getTopicId())) {
                ExceptionBuilder::throw(ChatErrorCode::TOPIC_ID_NOT_FOUND);
            }
            // assistantpreparestarthairmessage,endinputstatus
            $contentStruct = $aiSeqDTO->getContent();
            $isStream = $contentStruct instanceof StreamMessageInterface && $contentStruct->isStream();
            $beginStreamMessage = $isStream && $contentStruct instanceof StreamMessageInterface && $contentStruct->getStreamOptions()?->getStatus() === StreamMessageStatus::Start;
            if (! $isStream || $beginStreamMessage) {
                // nonstreamresponseor者streamresponsestartinput
                $this->delightfulConversationDomainService->agentOperateConversationStatusv2(
                    ControlMessageType::EndConversationInput,
                    $aiConversationEntity->getId(),
                    $aiSeqDTO->getExtra()?->getTopicId()
                );
            }
            // createuserAuth
            $userAuthorization = $this->getAgentAuth($aiUserEntity);
            // createmessage
            $messageDTO = $this->createAgentMessageDTO($aiSeqDTO, $aiUserEntity, $aiConversationEntity, $appMessageId, $sendTime);
            return $this->dispatchClientChatMessage($aiSeqDTO, $messageDTO, $userAuthorization, $aiConversationEntity);
        } catch (Throwable $exception) {
            $this->logger->error(Json::encode([
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]));
            throw $exception;
        }
    }

    /**
     * minutehairasyncmessagequeuemiddleseq.
     * such asaccording tohairitem方seq,for收item方generateseq,deliverseq.
     * @throws Throwable
     */
    public function asyncHandlerChatMessage(DelightfulSeqEntity $senderSeqEntity): void
    {
        Db::beginTransaction();
        try {
            # bydownischatmessage. 采取写扩散:ifis群,thenfor群membereachpersongenerateseq
            // 1.getconversationinfo
            $senderConversationEntity = $this->delightfulChatDomainService->getConversationById($senderSeqEntity->getConversationId());
            if ($senderConversationEntity === null) {
                $this->logger->error(sprintf('messageDispatchError conversation not found:%s', Json::encode($senderSeqEntity)));
                return;
            }
            $receiveConversationType = $senderConversationEntity->getReceiveType();
            $senderMessageEntity = $this->delightfulChatDomainService->getMessageByDelightfulMessageId($senderSeqEntity->getDelightfulMessageId());
            if ($senderMessageEntity === null) {
                $this->logger->error(sprintf('messageDispatchError senderMessageEntity not found:%s', Json::encode($senderSeqEntity)));
                return;
            }
            $delightfulSeqStatus = DelightfulMessageStatus::Unread;
            // according toconversationtype,generateseq
            switch ($receiveConversationType) {
                case ConversationType::Group:
                    $seqListCreateDTO = $this->delightfulChatDomainService->generateGroupReceiveSequence($senderSeqEntity, $senderMessageEntity, $delightfulSeqStatus);
                    // todo 群withinsurfacetopicmessagealsowrite topic_messages tablemiddle
                    // willthisthese seq_id mergeforoneitem mq messageconductpush/consume
                    $seqIds = array_keys($seqListCreateDTO);
                    $messagePriority = $this->delightfulChatDomainService->getChatMessagePriority(ConversationType::Group, count($seqIds));
                    ! empty($seqIds) && $this->delightfulChatDomainService->batchPushSeq($seqIds, $messagePriority);
                    break;
                case ConversationType::System:
                    throw new RuntimeException('To be implemented');
                case ConversationType::CloudDocument:
                    throw new RuntimeException('To be implemented');
                case ConversationType::MultidimensionalTable:
                    throw new RuntimeException('To be implemented');
                case ConversationType::Topic:
                    throw new RuntimeException('To be implemented');
                case ConversationType::App:
                    throw new RuntimeException('To be implemented');
            }
            Db::commit();
        } catch (Throwable$exception) {
            Db::rollBack();
            throw $exception;
        }
    }

    public function getTopicsByConversationId(DelightfulUserAuthorization $userAuthorization, string $conversationId, array $topicIds): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulChatDomainService->getTopicsByConversationId($dataIsolation, $conversationId, $topicIds);
    }

    /**
     * conversationwindowscrollloadmessage.
     */
    public function getMessagesByConversationId(DelightfulUserAuthorization $userAuthorization, string $conversationId, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        // conversation所have权validation
        $this->checkConversationsOwnership($userAuthorization, [$conversationId]);

        // 按timerange,getconversation/topicmessage
        $clientSeqList = $this->delightfulChatDomainService->getConversationChatMessages($conversationId, $conversationMessagesQueryDTO);
        return $this->formatConversationMessagesReturn($clientSeqList, $conversationMessagesQueryDTO);
    }

    /**
     * @deprecated
     */
    public function getMessageByConversationIds(DelightfulUserAuthorization $userAuthorization, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        // conversation所have权validation
        $conversationIds = $conversationMessagesQueryDTO->getConversationIds();
        if (! empty($conversationIds)) {
            $this->checkConversationsOwnership($userAuthorization, $conversationIds);
        }

        // getconversationmessage(notice,feature目andgetMessagesByConversationIddifferent)
        $clientSeqList = $this->delightfulChatDomainService->getConversationsChatMessages($conversationMessagesQueryDTO);
        return $this->formatConversationMessagesReturn($clientSeqList, $conversationMessagesQueryDTO);
    }

    // 按conversation id groupget几itemmostnewmessage
    public function getConversationsMessagesGroupById(DelightfulUserAuthorization $userAuthorization, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        // conversation所have权validation
        $conversationIds = $conversationMessagesQueryDTO->getConversationIds();
        if (! empty($conversationIds)) {
            $this->checkConversationsOwnership($userAuthorization, $conversationIds);
        }

        $clientSeqList = $this->delightfulChatDomainService->getConversationsMessagesGroupById($conversationMessagesQueryDTO);
        // 按conversation id group,return
        $conversationMessages = [];
        foreach ($clientSeqList as $clientSeq) {
            $conversationId = $clientSeq->getSeq()->getConversationId();
            $conversationMessages[$conversationId][] = $clientSeq->toArray();
        }
        return $conversationMessages;
    }

    public function intelligenceRenameTopicName(DelightfulUserAuthorization $authorization, string $topicId, string $conversationId): string
    {
        $history = $this->getConversationChatCompletionsHistory($authorization, $conversationId, 30, $topicId);
        if (empty($history)) {
            return '';
        }

        $historyContext = MessageAssembler::buildHistoryContext($history, 10000, $authorization->getNickname());
        return $this->summarizeText($authorization, $historyContext);
    }

    /**
     * usebigmodeltotextconductsummary.
     */
    public function summarizeText(DelightfulUserAuthorization $authorization, string $textContent, string $language = 'zh_CN'): string
    {
        if (empty($textContent)) {
            return '';
        }
        $prompt = <<<'PROMPT'
        youisone专业contenttitlegenerate助hand.请strict按照bydownrequireforconversationcontentgeneratetitle:

        ## taskgoal
        according toconversationcontent,generateoneconcise,accuratetitle,cansummarizeconversation核coretheme.

        ## themeprioritylevel原then
        whenconversation涉and多differentthemeo clock:
        1. priorityclose注conversationmiddlemostbackdiscussiontheme(mostnewtopic)
        2. bymost近conversationcontentformainreferencebasis
        3. ifmostbackthemediscussionmorefor充minute,thenbythisasfortitle核core
        4. ignore早期already经endtopic,unlessit们andmostnewtopic密切相close

        ## strictrequire
        1. titlelength:not超pass 15 character.Englishone字母算onecharacter,汉字one字算onecharacter,other语type采useanalogouscountsolution.
        2. content相close:titlemustdirectly反映conversation核coretheme
        3. languagestyle:use陈述property语sentence,avoid疑问sentence
        4. outputformat:onlyoutputtitlecontent,notwantaddanyexplain,标pointorothertext
        5. forbidlinefor:notwantreturn答conversationmiddleissue,notwantconduct额outsideexplain

        ## conversationcontent
        <CONVERSATION_START>
        {textContent}
        <CONVERSATION_END>

        ## outputlanguage
        <LANGUAGE_START>
        请use{language}languageoutputcontent
        <LANGUAGE_END>

        ## output
        请directlyoutputtitle:
        PROMPT;

        $prompt = str_replace(['{language}', '{textContent}'], [$language, $textContent], $prompt);

        $conversationId = uniqid('', true);
        $messageHistory = new MessageHistory();
        $messageHistory->addMessages(new SystemMessage($prompt), $conversationId);
        return $this->getSummaryFromLLM($authorization, $messageHistory, $conversationId);
    }

    /**
     * usebigmodeltotextconductsummary(usecustomizehint词).
     *
     * @param DelightfulUserAuthorization $authorization userauthorization
     * @param string $customPrompt completecustomizehint词(not做anyreplacehandle)
     * @return string generatetitle
     */
    public function summarizeTextWithCustomPrompt(DelightfulUserAuthorization $authorization, string $customPrompt): string
    {
        if (empty($customPrompt)) {
            return '';
        }

        $conversationId = uniqid('', true);
        $messageHistory = new MessageHistory();
        $messageHistory->addMessages(new SystemMessage($customPrompt), $conversationId);
        return $this->getSummaryFromLLM($authorization, $messageHistory, $conversationId);
    }

    public function getMessageReceiveList(string $messageId, DelightfulUserAuthorization $userAuthorization): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulChatDomainService->getMessageReceiveList($messageId, $dataIsolation);
    }

    /**
     * @param DelightfulChatFileEntity[] $fileUploadDTOs
     */
    public function fileUpload(array $fileUploadDTOs, DelightfulUserAuthorization $authorization): array
    {
        $dataIsolation = $this->createDataIsolation($authorization);
        return $this->delightfulChatFileDomainService->fileUpload($fileUploadDTOs, $dataIsolation);
    }

    /**
     * @param DelightfulChatFileEntity[] $fileDTOs
     * @return array<string,array>
     */
    public function getFileDownUrl(array $fileDTOs, DelightfulUserAuthorization $authorization): array
    {
        $dataIsolation = $this->createDataIsolation($authorization);
        // permissionvalidation,judgeusermessagemiddle,whethercontain本timehe想downloadfile
        $fileEntities = $this->delightfulChatFileDomainService->checkAndGetFilePaths($fileDTOs, $dataIsolation);
        // downloado clockalso原file原本name
        $downloadNames = [];
        $fileDownloadUrls = [];
        $filePaths = [];
        foreach ($fileEntities as $fileEntity) {
            // filter掉haveoutside链,butisnot file_key
            if (! empty($fileEntity->getExternalUrl()) && empty($fileEntity->getFileKey())) {
                $fileDownloadUrls[$fileEntity->getFileId()] = ['url' => $fileEntity->getExternalUrl()];
            } else {
                $downloadNames[$fileEntity->getFileKey()] = $fileEntity->getFileName();
            }
            if (! empty($fileEntity->getFileKey())) {
                $filePaths[$fileEntity->getFileId()] = $fileEntity->getFileKey();
            }
        }
        $fileKeys = array_values(array_unique(array_values($filePaths)));
        $links = $this->fileDomainService->getLinks($authorization->getOrganizationCode(), $fileKeys, null, $downloadNames);
        foreach ($filePaths as $fileId => $fileKey) {
            $fileLink = $links[$fileKey] ?? null;
            if (! $fileLink) {
                continue;
            }
            $fileDownloadUrls[$fileId] = $fileLink->toArray();
        }
        return $fileDownloadUrls;
    }

    /**
     * givehairitem方generatemessageandSeq.forguaranteesystemstableproperty,give收item方generatemessageandSeqstep放inmqasyncgo做.
     * !!! notice,transactionmiddledeliver mq,maybetransactionalsonotsubmit,mqmessagethenalreadybeconsume.
     * @throws Throwable
     */
    public function delightfulChat(
        DelightfulSeqEntity $senderSeqDTO,
        DelightfulMessageEntity $senderMessageDTO,
        DelightfulConversationEntity $senderConversationEntity
    ): array {
        // givehairitem方generatemessageandSeq
        // frommessageStructmiddleparseoutcomeconversationwindowdetail
        $receiveType = $senderConversationEntity->getReceiveType();
        if (! in_array($receiveType, [ConversationType::Ai, ConversationType::User, ConversationType::Group], true)) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_TYPE_ERROR);
        }

        $language = CoContext::getLanguage();
        // auditrequirement:ifiseditmessage,writemessageversiontable,andupdate原messageversion_id
        $extra = $senderSeqDTO->getExtra();
        // settinglanguageinfo
        $editMessageOptions = $extra?->getEditMessageOptions();
        if ($extra !== null && $editMessageOptions !== null && ! empty($editMessageOptions->getDelightfulMessageId())) {
            $senderMessageDTO->setDelightfulMessageId($editMessageOptions->getDelightfulMessageId());
            $messageVersionEntity = $this->delightfulChatDomainService->editMessage($senderMessageDTO);
            $editMessageOptions->setMessageVersionId($messageVersionEntity->getVersionId());
            $senderSeqDTO->setExtra($extra->setEditMessageOptions($editMessageOptions));
            // again查onetime $messageEntity ,avoidduplicatecreate
            $messageEntity = $this->delightfulChatDomainService->getMessageByDelightfulMessageId($senderMessageDTO->getDelightfulMessageId());
            $messageEntity && $messageEntity->setLanguage($language);
        }

        // ifquotemessagebeeditpass,that么modify referMessageId fororiginalmessage id
        $this->checkAndUpdateReferMessageId($senderSeqDTO);

        $senderMessageDTO->setLanguage($language);

        $messageStruct = $senderMessageDTO->getContent();
        if ($messageStruct instanceof StreamMessageInterface && $messageStruct->isStream()) {
            // streammessagescenario
            if ($messageStruct->getStreamOptions()->getStatus() === StreamMessageStatus::Start) {
                // ifisstart,call createAndSendStreamStartSequence method
                $senderSeqEntity = $this->delightfulChatDomainService->createAndSendStreamStartSequence(
                    (new CreateStreamSeqDTO())->setTopicId($extra->getTopicId())->setAppMessageId($senderMessageDTO->getAppMessageId()),
                    $messageStruct,
                    $senderConversationEntity
                );
                $senderMessageId = $senderSeqEntity->getMessageId();
                $delightfulMessageId = $senderSeqEntity->getDelightfulMessageId();
            } else {
                $streamCachedDTO = $this->delightfulChatDomainService->streamSendJsonMessage(
                    $senderMessageDTO->getAppMessageId(),
                    $senderMessageDTO->getContent()->toArray(true),
                    $messageStruct->getStreamOptions()->getStatus()
                );
                $senderMessageId = $streamCachedDTO->getSenderMessageId();
                $delightfulMessageId = $streamCachedDTO->getDelightfulMessageId();
            }
            // onlyincertain $senderSeqEntity and $messageEntity,useatreturndatastructure
            $senderSeqEntity = $this->delightfulSeqDomainService->getSeqEntityByMessageId($senderMessageId);
            $messageEntity = $this->delightfulChatDomainService->getMessageByDelightfulMessageId($delightfulMessageId);
            // willmessagestreamreturngivecurrentcustomer端! butisalsoiswillasyncpushgiveuser所haveonlinecustomer端.
            return SeqAssembler::getClientSeqStruct($senderSeqEntity, $messageEntity)->toArray();
        }

        # nonstreammessage
        try {
            Db::beginTransaction();
            if (! isset($messageEntity)) {
                $messageEntity = $this->delightfulChatDomainService->createDelightfulMessageByAppClient($senderMessageDTO, $senderConversationEntity);
            }
            // givefrom己messagestreamgenerate序column,andcertainmessagereceivepersoncolumntable
            $senderSeqEntity = $this->delightfulChatDomainService->generateSenderSequenceByChatMessage($senderSeqDTO, $messageEntity, $senderConversationEntity);
            // avoid seq tablecarrytoo多feature,addtoo多index,thereforewilltopicmessagesingle独writeto topic_messages tablemiddle
            $this->delightfulChatDomainService->createTopicMessage($senderSeqEntity);
            // certainmessageprioritylevel
            $receiveList = $senderSeqEntity->getReceiveList();
            if ($receiveList === null) {
                $receiveUserCount = 0;
            } else {
                $receiveUserCount = count($receiveList->getUnreadList());
            }
            $senderChatSeqCreatedEvent = $this->delightfulChatDomainService->getChatSeqCreatedEvent(
                $messageEntity->getReceiveType(),
                $senderSeqEntity,
                $receiveUserCount,
            );
            $conversationType = $senderConversationEntity->getReceiveType();
            if (in_array($conversationType, [ConversationType::Ai, ConversationType::User], true)) {
                // forguarantee收hairdouble方messageorderone致property,ifisprivate chat,thensyncgenerate seq
                $receiveSeqEntity = $this->syncHandlerSingleChatMessage($senderSeqEntity, $messageEntity);
            } elseif ($conversationType === ConversationType::Group) {
                // group chatetcscenarioasyncgive收item方generateSeqandpushgive收item方
                $this->delightfulChatDomainService->dispatchSeq($senderChatSeqCreatedEvent);
            } else {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_TYPE_ERROR);
            }
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            throw $exception;
        }
        // use mq pushmessagegive收item方
        isset($receiveSeqEntity) && $this->pushReceiveChatSequence($messageEntity, $receiveSeqEntity);
        // asyncpushmessagegivefrom己other设备
        if ($messageEntity->getSenderType() !== ConversationType::Ai) {
            co(function () use ($senderChatSeqCreatedEvent) {
                $this->delightfulChatDomainService->pushChatSequence($senderChatSeqCreatedEvent);
            });
        }

        // ifiseditmessage,andisusereditassistanthaircomeapprovalformo clock,returnnullarray.
        // 因forthiso clockcreate seq_id isassistant,notisuser,returnwill造become困扰.
        // 经by mq minutehairmessageback,userwillasync收to属athefrom己messagepush.
        if (isset($editMessageOptions) && ! empty($editMessageOptions->getDelightfulMessageId())
            && $messageEntity->getSenderId() !== $senderMessageDTO->getSenderId()) {
            return [];
        }

        // willmessagestreamreturngivecurrentcustomer端! butisalsoiswillasyncpushgiveuser所haveonlinecustomer端.
        return SeqAssembler::getClientSeqStruct($senderSeqEntity, $messageEntity)->toArray();
    }

    /**
     * ifquotemessagebeeditpass,that么modify referMessageId fororiginalmessage id.
     */
    public function checkAndUpdateReferMessageId(DelightfulSeqEntity $senderSeqDTO): void
    {
        // getquotemessageID
        $referMessageId = $senderSeqDTO->getReferMessageId();
        if (empty($referMessageId)) {
            return;
        }

        // querybequotemessage
        $delightfulSeqEntity = $this->delightfulSeqDomainService->getSeqEntityByMessageId($referMessageId);
        if ($delightfulSeqEntity === null || empty($delightfulSeqEntity->getDelightfulMessageId())) {
            ExceptionBuilder::throw(ChatErrorCode::REFER_MESSAGE_NOT_FOUND);
        }

        if (empty($delightfulSeqEntity->getExtra()?->getEditMessageOptions()?->getDelightfulMessageId())) {
            return;
        }
        // get message min seqEntity
        $delightfulSeqEntity = $this->delightfulSeqDomainService->getSelfMinSeqIdByDelightfulMessageId($delightfulSeqEntity);
        if ($delightfulSeqEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::REFER_MESSAGE_NOT_FOUND);
        }
        // 便atfrontclient rendering,updatequotemessageIDfororiginalmessageID
        $senderSeqDTO->setReferMessageId($delightfulSeqEntity->getMessageId());
    }

    /**
     * openhair阶segment,front端to接havetimedifference,updown文compatiblepropertyhandle.
     */
    public function setUserContext(string $userToken, ?DelightfulContext $delightfulContext): void
    {
        if (! $delightfulContext) {
            ExceptionBuilder::throw(ChatErrorCode::CONTEXT_LOST);
        }
        // forsupportonews链receivehair多账numbermessage,allowinmessageupdown文middlepass in账number token
        if (! $delightfulContext->getAuthorization()) {
            $delightfulContext->setAuthorization($userToken);
        }
        // 协程updown文middlesettinguserinfo,供 WebsocketChatUserGuard use
        WebSocketContext::set(DelightfulContext::class, $delightfulContext);
    }

    /**
     * chatwindow打字o clock补alluserinput.foradaptgroup chat,thiswithin role its实isusernickname,whilenotisroletype.
     */
    public function getConversationChatCompletionsHistory(
        DelightfulUserAuthorization $userAuthorization,
        string $conversationId,
        int $limit,
        string $topicId,
        bool $useNicknameAsRole = true
    ): array {
        $conversationMessagesQueryDTO = new MessagesQueryDTO();
        $conversationMessagesQueryDTO->setConversationId($conversationId)->setLimit($limit)->setTopicId($topicId);
        // gettopicmost近 20 itemconversationrecord
        $clientSeqResponseDTOS = $this->delightfulChatDomainService->getConversationChatMessages($conversationId, $conversationMessagesQueryDTO);
        // get收hairdouble方userinfo,useat补allo clockenhanceroletype
        $userIds = [];
        foreach ($clientSeqResponseDTOS as $clientSeqResponseDTO) {
            // 收collection user_id
            $userIds[] = $clientSeqResponseDTO->getSeq()->getMessage()->getSenderId();
        }
        // from己 user_id alsoaddentergo
        $userIds[] = $userAuthorization->getId();
        // go重
        $userIds = array_values(array_unique($userIds));
        $userEntities = $this->delightfulUserDomainService->getUserByIdsWithoutOrganization($userIds);
        /** @var DelightfulUserEntity[] $userEntities */
        $userEntities = array_column($userEntities, null, 'user_id');
        $userMessages = [];
        foreach ($clientSeqResponseDTOS as $clientSeqResponseDTO) {
            $senderUserId = $clientSeqResponseDTO->getSeq()->getMessage()->getSenderId();
            $delightfulUserEntity = $userEntities[$senderUserId] ?? null;
            if ($delightfulUserEntity === null) {
                continue;
            }
            $message = $clientSeqResponseDTO->getSeq()->getMessage()->getContent();
            // 暂o clockonlyhandleuserinput,byandcanget纯textmessagetype
            $messageContent = $this->getMessageTextContent($message);
            if (empty($messageContent)) {
                continue;
            }

            // according toparameterdecideusenicknamealsoistraditional role
            if ($useNicknameAsRole) {
                $userMessages[$clientSeqResponseDTO->getSeq()->getSeqId()] = [
                    'role' => $delightfulUserEntity->getNickname(),
                    'role_description' => $delightfulUserEntity->getDescription(),
                    'content' => $messageContent,
                ];
            } else {
                // usetraditional role,judgewhetherfor AI user
                $isAiUser = $delightfulUserEntity->getUserType() === UserType::Ai;
                $role = $isAiUser ? Role::Assistant : Role::User;

                $userMessages[$clientSeqResponseDTO->getSeq()->getSeqId()] = [
                    'role' => $role->value,
                    'content' => $messageContent,
                ];
            }
        }
        if (empty($userMessages)) {
            return [];
        }
        // according to seq_id ascendingrowcolumn
        ksort($userMessages);
        return array_values($userMessages);
    }

    public function getDelightfulSeqEntity(string $delightfulMessageId, ConversationType $controlMessageType): ?DelightfulSeqEntity
    {
        $seqEntities = $this->delightfulSeqDomainService->getSeqEntitiesByDelightfulMessageId($delightfulMessageId);
        foreach ($seqEntities as $seqEntity) {
            if ($seqEntity->getObjectType() === $controlMessageType) {
                return $seqEntity;
            }
        }
        return null;
    }

    /**
     * Check if message has already been sent to avoid duplicate sending.
     *
     * @param string $appMessageId Application message ID (should be primary key from external table)
     * @param string $messageType Optional message type filter (empty string means check all types)
     * @return bool True if message already sent, false if not sent or check failed
     */
    public function isMessageAlreadySent(string $appMessageId, string $messageType = ''): bool
    {
        if (empty($appMessageId)) {
            $this->logger->warning('Empty appMessageId provided for duplicate check');
            return false;
        }

        try {
            $exists = $this->delightfulChatDomainService->isMessageAlreadySent($appMessageId, $messageType);

            if ($exists) {
                $this->logger->info(sprintf(
                    'Message already sent - App Message ID: %s, Message Type: %s',
                    $appMessageId,
                    $messageType ?: 'any'
                ));
            }

            return $exists;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Error checking message duplication: %s, App Message ID: %s, Message Type: %s',
                $e->getMessage(),
                $appMessageId,
                $messageType ?: 'any'
            ));

            // Return false to allow sending when check fails (fail-safe approach)
            return false;
        }
    }

    /**
     * Check the legality of editing a message.
     * Verify that the message to be edited meets one of the following conditions:
     * 1. The current user is the message sender
     * 2. The message is sent by an agent and the current user is the message receiver.
     *
     * @param DelightfulSeqEntity $senderSeqDTO Sender sequence DTO
     * @param DataIsolation $dataIsolation Data isolation object
     * @throws Throwable
     */
    protected function checkEditMessageLegality(
        DelightfulSeqEntity $senderSeqDTO,
        DataIsolation $dataIsolation
    ): void {
        // Check if this is an edit message operation
        $editMessageOptions = $senderSeqDTO->getExtra()?->getEditMessageOptions();
        if ($editMessageOptions === null) {
            return;
        }

        $delightfulMessageId = $editMessageOptions->getDelightfulMessageId();
        if (empty($delightfulMessageId)) {
            return;
        }

        try {
            // Get the message entity to be edited
            $messageEntity = $this->delightfulChatDomainService->getMessageByDelightfulMessageId($delightfulMessageId);
            if ($messageEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::MESSAGE_NOT_FOUND);
            }

            // Case 1: Check if the current user is the message sender
            if ($this->isCurrentUserMessage($messageEntity, $dataIsolation)) {
                return; // User can edit their own messages
            }

            // Case 2: Check if the message is sent by an agent to the current user
            if ($this->isAgentMessageToCurrentUser($messageEntity, $delightfulMessageId, $dataIsolation)) {
                return; // User can edit agent messages sent to them
            }

            // If neither condition is met, reject the edit
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_NOT_FOUND);
        } catch (Throwable $exception) {
            $this->logger->error(sprintf(
                'checkEditMessageLegality error: %s, delightfulMessageId: %s, currentUserId: %s',
                $exception->getMessage(),
                $delightfulMessageId,
                $dataIsolation->getCurrentUserId()
            ));
            throw $exception;
        }
    }

    /**
     * forguarantee收hairdouble方messageorderone致property,ifisprivate chat,thensyncgenerate seq.
     * @throws Throwable
     */
    private function syncHandlerSingleChatMessage(DelightfulSeqEntity $senderSeqEntity, DelightfulMessageEntity $senderMessageEntity): DelightfulSeqEntity
    {
        $delightfulSeqStatus = DelightfulMessageStatus::Unread;
        # assistantmaybe参andprivate chat/group chatetcscenario,read记忆o clock,needreadfrom己conversationwindowdownmessage.
        $receiveSeqEntity = $this->delightfulChatDomainService->generateReceiveSequenceByChatMessage($senderSeqEntity, $senderMessageEntity, $delightfulSeqStatus);
        // avoid seq tablecarrytoo多feature,addtoo多index,thereforewilltopicmessagesingle独writeto topic_messages tablemiddle
        $this->delightfulChatDomainService->createTopicMessage($receiveSeqEntity);
        return $receiveSeqEntity;
    }

    /**
     * usebigmodelgeneratecontentsummary
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     * @param MessageHistory $messageHistory messagehistory
     * @param string $conversationId conversationID
     * @param string $topicId topicID,optional
     * @return string generatesummarytext
     */
    private function getSummaryFromLLM(
        DelightfulUserAuthorization $authorization,
        MessageHistory $messageHistory,
        string $conversationId,
        string $topicId = ''
    ): string {
        $orgCode = $authorization->getOrganizationCode();
        $dataIsolation = $this->createDataIsolation($authorization);
        $chatModelName = di(ModelConfigAppService::class)->getChatModelTypeByFallbackChain($orgCode, $dataIsolation->getCurrentUserId(), LLMModelEnum::DEEPSEEK_V3->value);

        $modelGatewayMapperDataIsolation = ModelGatewayDataIsolation::createByOrganizationCodeWithoutSubscription($dataIsolation->getCurrentOrganizationCode(), $dataIsolation->getCurrentUserId());
        # startrequestbigmodel
        $modelGatewayMapper = di(ModelGatewayMapper::class);
        $model = $modelGatewayMapper->getChatModelProxy($modelGatewayMapperDataIsolation, $chatModelName);
        $memoryManager = $messageHistory->getMemoryManager($conversationId);
        $agent = AgentFactory::create(
            model: $model,
            memoryManager: $memoryManager,
            temperature: 0.6,
            businessParams: [
                'organization_id' => $dataIsolation->getCurrentOrganizationCode(),
                'user_id' => $dataIsolation->getCurrentUserId(),
                'business_id' => $topicId ?: $conversationId,
                'source_id' => 'summary_content',
            ],
        );

        $chatCompletionResponse = $agent->chatAndNotAutoExecuteTools();
        $choiceContent = $chatCompletionResponse->getFirstChoice()?->getMessage()->getContent();
        // iftitlelength超pass20characterthenbacksurfaceuse...replace
        if (mb_strlen($choiceContent) > 20) {
            $choiceContent = mb_substr($choiceContent, 0, 20) . '...';
        }

        return $choiceContent;
    }

    private function getMessageTextContent(MessageInterface $message): string
    {
        // 暂o clockonlyhandleuserinput,byandcanget纯textmessagetype
        if ($message instanceof TextContentInterface) {
            $messageContent = $message->getTextContent();
        } else {
            $messageContent = '';
        }
        return $messageContent;
    }

    /**
     * @param ClientSequenceResponse[] $clientSeqList
     */
    private function formatConversationMessagesReturn(array $clientSeqList, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        $data = [];
        foreach ($clientSeqList as $clientSeq) {
            $seqId = $clientSeq->getSeq()->getSeqId();
            $data[$seqId] = $clientSeq->toArray();
        }
        $hasMore = (count($clientSeqList) === $conversationMessagesQueryDTO->getLimit());
        // 按照 $order indatabasemiddlequery,butistoreturnresultcollectiondescendingrowcolumn.
        $order = $conversationMessagesQueryDTO->getOrder();
        if ($order === Order::Desc) {
            // to $data descendingrowcolumn
            krsort($data);
        } else {
            // to $data ascendingrowcolumn
            ksort($data);
        }
        $pageToken = (string) array_key_last($data);
        return PageListAssembler::pageByElasticSearch(array_values($data), $pageToken, $hasMore);
    }

    private function getAgentAuth(DelightfulUserEntity $aiUserEntity): DelightfulUserAuthorization
    {
        // createuserAuth
        $userAuthorization = new DelightfulUserAuthorization();
        $userAuthorization->setStatus((string) $aiUserEntity->getStatus()->value);
        $userAuthorization->setId($aiUserEntity->getUserId());
        $userAuthorization->setNickname($aiUserEntity->getNickname());
        $userAuthorization->setOrganizationCode($aiUserEntity->getOrganizationCode());
        $userAuthorization->setDelightfulId($aiUserEntity->getDelightfulId());
        $userAuthorization->setUserType($aiUserEntity->getUserType());
        return $userAuthorization;
    }

    private function createAgentMessageDTO(
        DelightfulSeqEntity $aiSeqDTO,
        DelightfulUserEntity $aiUserEntity,
        DelightfulConversationEntity $aiConversationEntity,
        string $appMessageId,
        Carbon $sendTime
    ): DelightfulMessageEntity {
        // createmessage
        $messageDTO = new DelightfulMessageEntity();
        $messageDTO->setMessageType($aiSeqDTO->getSeqType());
        $messageDTO->setSenderId($aiUserEntity->getUserId());
        $messageDTO->setSenderType(ConversationType::Ai);
        $messageDTO->setSenderOrganizationCode($aiUserEntity->getOrganizationCode());
        $messageDTO->setReceiveId($aiConversationEntity->getReceiveId());
        $messageDTO->setReceiveType(ConversationType::User);
        $messageDTO->setReceiveOrganizationCode($aiConversationEntity->getReceiveOrganizationCode());
        $messageDTO->setAppMessageId($appMessageId);
        $messageDTO->setDelightfulMessageId('');
        $messageDTO->setSendTime($sendTime->toDateTimeString());
        // typeandcontentgroup合inoneup才isonecanusemessagetype
        $messageDTO->setContent($aiSeqDTO->getContent());
        $messageDTO->setMessageType($aiSeqDTO->getSeqType());
        return $messageDTO;
    }

    private function pushReceiveChatSequence(DelightfulMessageEntity $messageEntity, DelightfulSeqEntity $seq): void
    {
        $receiveType = $messageEntity->getReceiveType();
        $seqCreatedEvent = $this->delightfulChatDomainService->getChatSeqPushEvent($receiveType, $seq->getSeqId(), 1);
        $this->delightfulChatDomainService->pushChatSequence($seqCreatedEvent);
    }

    /**
     * according tocustomer端haircomechatmessagetype,minutehairtoto应handle模piece.
     * @throws Throwable
     */
    private function dispatchClientChatMessage(
        DelightfulSeqEntity $senderSeqDTO,
        DelightfulMessageEntity $senderMessageDTO,
        DelightfulUserAuthorization $userAuthorization,
        DelightfulConversationEntity $senderConversationEntity
    ): array {
        $lockKey = sprintf('messageDispatch:lock:%s', $senderConversationEntity->getId());
        $owner = uniqid('', true);
        try {
            $this->locker->spinLock($lockKey, $owner, 5);
            $chatMessageType = $senderMessageDTO->getMessageType();
            if (! $chatMessageType instanceof ChatMessageType) {
                ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR);
            }
            $dataIsolation = $this->createDataIsolation($userAuthorization);
            // messageauthentication
            $this->checkSendMessageAuth($senderSeqDTO, $senderMessageDTO, $senderConversationEntity, $dataIsolation);
            // securitypropertyguarantee,validationattachmentmiddlefilewhether属atcurrentuser
            $senderMessageDTO = $this->checkAndFillAttachments($senderMessageDTO, $dataIsolation);
            // businessparametervalidation
            $this->validateBusinessParams($senderMessageDTO, $dataIsolation);
            // messageminutehair
            $conversationType = $senderConversationEntity->getReceiveType();
            return match ($conversationType) {
                ConversationType::Ai,
                ConversationType::User,
                ConversationType::Group => $this->delightfulChat($senderSeqDTO, $senderMessageDTO, $senderConversationEntity),
                default => ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_TYPE_ERROR),
            };
        } finally {
            $this->locker->release($lockKey, $owner);
        }
    }

    /**
     * validationattachmentmiddlefilewhether属atcurrentuser,andpopulateattachmentinfo.(file名/typeetcfield).
     */
    private function checkAndFillAttachments(DelightfulMessageEntity $senderMessageDTO, DataIsolation $dataIsolation): DelightfulMessageEntity
    {
        $content = $senderMessageDTO->getContent();
        if (! $content instanceof AbstractAttachmentMessage) {
            return $senderMessageDTO;
        }
        $attachments = $content->getAttachments();
        if (empty($attachments)) {
            return $senderMessageDTO;
        }
        $attachments = $this->delightfulChatFileDomainService->checkAndFillAttachments($attachments, $dataIsolation);
        $content->setAttachments($attachments);
        return $senderMessageDTO;
    }

    /**
     * Check if the message is sent by the current user.
     */
    private function isCurrentUserMessage(DelightfulMessageEntity $messageEntity, DataIsolation $dataIsolation): bool
    {
        return $messageEntity->getSenderId() === $dataIsolation->getCurrentUserId();
    }

    /**
     * Check if the message is sent by an agent to the current user.
     */
    private function isAgentMessageToCurrentUser(DelightfulMessageEntity $messageEntity, string $delightfulMessageId, DataIsolation $dataIsolation): bool
    {
        // First check if the message is sent by an agent
        if ($messageEntity->getSenderType() !== ConversationType::Ai) {
            return false;
        }

        // Get all seq entities for this message
        $seqEntities = $this->delightfulSeqDomainService->getSeqEntitiesByDelightfulMessageId($delightfulMessageId);
        if (empty($seqEntities)) {
            return false;
        }

        // Check if the current user is the receiver of this message
        $currentDelightfulId = $dataIsolation->getCurrentDelightfulId();
        foreach ($seqEntities as $seqEntity) {
            if ($seqEntity->getObjectId() === $currentDelightfulId) {
                return true;
            }
        }

        return false;
    }

    /**
     * checkconversation所have权
     * ensure所haveconversationIDall属atcurrent账number,nothenthrowexception.
     *
     * @param DelightfulUserAuthorization $userAuthorization userauthorizationinfo
     * @param array $conversationIds 待checkconversationIDarray
     */
    private function checkConversationsOwnership(DelightfulUserAuthorization $userAuthorization, array $conversationIds): void
    {
        if (empty($conversationIds)) {
            return;
        }

        // batchquantitygetconversationinfo
        $conversations = $this->delightfulChatDomainService->getConversationsByIds($conversationIds);
        if (empty($conversations)) {
            return;
        }

        // 收collection所haveconversationassociateuserID
        $userIds = [];
        foreach ($conversations as $conversation) {
            $userIds[] = $conversation->getUserId();
        }
        $userIds = array_unique($userIds);

        // batchquantitygetuserinfo
        $userEntities = $this->delightfulUserDomainService->getUserByIdsWithoutOrganization($userIds);
        $userMap = array_column($userEntities, 'delightful_id', 'user_id');

        // checkeachconversationwhether属atcurrentuser(passdelightful_idmatch)
        $currentDelightfulId = $userAuthorization->getDelightfulId();
        foreach ($conversationIds as $id) {
            $conversationEntity = $conversations[$id] ?? null;
            if (! isset($conversationEntity)) {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
            }

            $userId = $conversationEntity->getUserId();
            $userDelightfulId = $userMap[$userId] ?? null;

            if ($userDelightfulId !== $currentDelightfulId) {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
            }
        }
    }

    /**
     * businessparametervalidation
     * to特定typemessageconductbusinessrulevalidation.
     */
    private function validateBusinessParams(DelightfulMessageEntity $senderMessageDTO, DataIsolation $dataIsolation): void
    {
        $content = $senderMessageDTO->getContent();
        $messageType = $senderMessageDTO->getMessageType();

        // voicemessagevalidation
        if ($messageType === ChatMessageType::Voice && $content instanceof VoiceMessage) {
            $this->validateVoiceMessageParams($content, $dataIsolation);
        }
    }

    /**
     * validationvoicemessagebusinessparameter.
     */
    private function validateVoiceMessageParams(VoiceMessage $voiceMessage, DataIsolation $dataIsolation): void
    {
        // validationattachment
        $attachments = $voiceMessage->getAttachments();
        if (empty($attachments)) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, 'chat.message.voice.attachment_required');
        }

        if (count($attachments) !== 1) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, 'chat.message.voice.single_attachment_only', ['count' => count($attachments)]);
        }

        // usenew getAttachment() methodgetfirstattachment
        $attachment = $voiceMessage->getAttachment();
        if ($attachment === null) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, 'chat.message.voice.attachment_empty');
        }

        // according toaudio file_id callfiledomaingetdetail,andpopulateattachmentmissingpropertyvalue
        $this->fillVoiceAttachmentDetails($voiceMessage, $dataIsolation);

        // 重newgetpopulatebackattachment
        $attachment = $voiceMessage->getAttachment();

        if ($attachment->getFileType() !== FileType::Audio) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, 'chat.message.voice.audio_format_required', ['type' => $attachment->getFileType()->name]);
        }

        // validationrecordingduration
        $duration = $voiceMessage->getDuration();
        if ($duration !== null) {
            if ($duration <= 0) {
                ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, 'chat.message.voice.duration_positive', ['duration' => $duration]);
            }

            // defaultmostbig60second
            $maxDuration = 60;
            if ($duration > $maxDuration) {
                ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, 'chat.message.voice.duration_exceeds_limit', ['max_duration' => $maxDuration, 'duration' => $duration]);
            }
        }
    }

    /**
     * according toaudio file_id callfiledomaingetdetail,andpopulate VoiceMessage inherit ChatAttachment missingpropertyvalue.
     */
    private function fillVoiceAttachmentDetails(VoiceMessage $voiceMessage, DataIsolation $dataIsolation): void
    {
        $attachments = $voiceMessage->getAttachments();
        if (empty($attachments)) {
            return;
        }

        // callfiledomainservicepopulateattachmentdetail
        $filledAttachments = $this->delightfulChatFileDomainService->checkAndFillAttachments($attachments, $dataIsolation);

        // updatevoicemessageattachmentinfo
        $voiceMessage->setAttachments($filledAttachments);
    }
}
