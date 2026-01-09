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
 * chatmessagerelatedclose.
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
        // will have sid alladdinputtoroom id valuefor delightfulId roommiddle
        $this->delightfulChatDomainService->joinRoom($userAuthorization->getDelightfulId(), $socket);
    }

    /**
     * returnmostbigmessagecountdown n itemsequencecolumn.
     * @return ClientSequenceResponse[]
     * @deprecated
     */
    public function pullMessage(DelightfulUserAuthorization $userAuthorization, array $params): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulChatDomainService->pullMessage($dataIsolation, $params);
    }

    /**
     * returnmostbigmessagecountdown n itemsequencecolumn.
     * @return ClientSequenceResponse[]
     */
    public function pullByPageToken(DelightfulUserAuthorization $userAuthorization, array $params): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $pageSize = 200;
        return $this->delightfulChatDomainService->pullByPageToken($dataIsolation, $params, $pageSize);
    }

    /**
     * returnmostbigmessagecountdown n itemsequencecolumn.
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
        // seq extensioninfo. ifneedretrievetopicmessage,pleasequery topic_messages table
        $topicId && $seqExtra->setTopicId($topicId);
        $seqDTO->setExtra($seqExtra);
        // ifisfollowassistantprivate chat,andnothavetopic id,fromautocreateonetopic
        if ($conversationEntity->getReceiveType() === ConversationType::Ai && empty($seqDTO->getExtra()?->getTopicId())) {
            $topicId = $this->delightfulTopicDomainService->agentSendMessageGetTopicId($conversationEntity, 0);
            // notimpactoriginalhavelogic,will topicId settingto extra middle
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
        // checkconversation idbelong toorganization,andcurrentpass inorganizationencodingonetoproperty
        if ($conversationEntity->getUserOrganizationCode() !== $dataIsolation->getCurrentOrganizationCode()) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // judgeconversationhairuppersonwhetheriscurrentuser,andandnotisassistant
        if ($conversationEntity->getReceiveType() !== ConversationType::Ai && $conversationEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // conversationwhetheralreadybedelete
        if ($conversationEntity->getStatus() === ConversationStatus::Delete) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_DELETED);
        }
        // ifiseditmessage,checkbeeditmessagelegalproperty(fromselfhairmessage,andincurrentconversationmiddle)
        $this->checkEditMessageLegality($senderSeqDTO, $dataIsolation);
        return;
        // todo ifmessagemiddlehavefile:1.judgefile havepersonwhetheriscurrentuser;2.judgeuserwhetherreceivepassthisthesefile.
        /* @phpstan-ignore-next-line */
        $messageContent = $senderMessageDTO->getContent();
        if ($messageContent instanceof ChatFileInterface) {
            $fileIds = $messageContent->getFileIds();
            if (! empty($fileIds)) {
                // batchquantityqueryfile havepermission,whilenotisloopquery
                $fileEntities = $this->delightfulChatFileDomainService->getFileEntitiesByFileIds($fileIds);

                // checkwhether havefileallexistsin
                $existingFileIds = array_map(static function (DelightfulChatFileEntity $fileEntity) {
                    return $fileEntity->getFileId();
                }, $fileEntities);

                // checkwhetherhaverequestfile ID notinalreadyquerytofile ID middle
                $missingFileIds = array_diff($fileIds, $existingFileIds);
                if (! empty($missingFileIds)) {
                    ExceptionBuilder::throw(ChatErrorCode::FILE_NOT_FOUND);
                }

                // checkfile havepersonwhetheriscurrentuser
                foreach ($fileEntities as $fileEntity) {
                    if ($fileEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
                        ExceptionBuilder::throw(ChatErrorCode::FILE_NOT_FOUND);
                    }
                }
            }
        }

        // todo checkwhetherhavehairmessagepermission(needhavegoodfriendclosesystem,enterpriseclosesystem,collection团closesystem,combineaspartnerclosesystemetc)
    }

    /**
     * assistantgivepersoncategoryorgrouphairmessage,supportonlinemessageandofflinemessage(depend onatuserwhetheronline).
     * @param DelightfulSeqEntity $aiSeqDTO how to pass parameterscanreference apilayer aiSendMessage method
     * @param string $appMessageId messageprevent duplicate,customerclient(includeflow)fromselftomessagegenerateoneitemencoding
     * @param bool $doNotParseReferMessageId notby chat judge referMessageId quoteo clockmachine,bycallsidefromselfjudge
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
            // ifusergiveassistantsendmultipleitemmessage,assistantreplyo clock,needletuserawareassistantreplyishewhichitemmessage.
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
                // nonstreamresponseorpersonstreamresponsestartinput
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
     * assistantgivepersoncategoryorgrouphairmessage,cannotpassconversationandtopic id,fromautocreateconversation,nongroupconversationfromautoadapttopic id.
     * @param string $appMessageId messageprevent duplicate,customerclient(includeflow)fromselftomessagegenerateoneitemencoding
     * @param bool $doNotParseReferMessageId cannotby chat judge referMessageId quoteo clockmachine,bycallsidefromselfjudge
     * @throws Throwable
     */
    public function agentSendMessage(
        DelightfulSeqEntity $aiSeqDTO,
        string $senderUserId,
        string $receiverId,
        string $appMessageId = '',
        bool $doNotParseReferMessageId = false,// cannotby chat judge referMessageId quoteo clockmachine,bycallsidefromselfjudge
        ?Carbon $sendTime = null,
        ?ConversationType $receiverType = null
    ): array {
        // 1.judge $senderUserId and $receiverUserIdconversationwhetherexistsin(referencegetOrCreateConversationmethod)
        $senderConversationEntity = $this->delightfulConversationDomainService->getOrCreateConversation($senderUserId, $receiverId, $receiverType);
        // alsowantcreatereceivesideconversationwindow,wantnotthennomethodcreatetopic
        $this->delightfulConversationDomainService->getOrCreateConversation($receiverId, $senderUserId);

        // 2.if $seqExtra notfor null,validationwhetherhave topic id,ifnothave,reference agentSendMessageGetTopicId method,totopic id
        $topicId = $aiSeqDTO->getExtra()?->getTopicId() ?? '';
        if (empty($topicId) && $receiverType !== ConversationType::Group) {
            $topicId = $this->delightfulTopicDomainService->agentSendMessageGetTopicId($senderConversationEntity, 0);
        }
        // 3.groupinstallparameter,call aiSendMessage method
        $aiSeqDTO->getExtra() === null && $aiSeqDTO->setExtra(new SeqExtra());
        $aiSeqDTO->getExtra()->setTopicId($topicId);
        $aiSeqDTO->setConversationId($senderConversationEntity->getId());
        return $this->aiSendMessage($aiSeqDTO, $appMessageId, $sendTime, $doNotParseReferMessageId);
    }

    /**
     * personcategorygiveassistantorgrouphairmessage,cannotpassconversationandtopic id,fromautocreateconversation,nongroupconversationfromautoadapttopic id.
     * @param string $appMessageId messageprevent duplicate,customerclient(includeflow)fromselftomessagegenerateoneitemencoding
     * @param bool $doNotParseReferMessageId cannotby chat judge referMessageId quoteo clockmachine,bycallsidefromselfjudge
     * @throws Throwable
     */
    public function userSendMessageToAgent(
        DelightfulSeqEntity $aiSeqDTO,
        string $senderUserId,
        string $receiverId,
        string $appMessageId = '',
        bool $doNotParseReferMessageId = false,// cannotby chat judge referMessageId quoteo clockmachine,bycallsidefromselfjudge
        ?Carbon $sendTime = null,
        ?ConversationType $receiverType = null,
        string $topicId = ''
    ): array {
        // 1.judge $senderUserId and $receiverUserIdconversationwhetherexistsin(referencegetOrCreateConversationmethod)
        $senderConversationEntity = $this->delightfulConversationDomainService->getOrCreateConversation($senderUserId, $receiverId, $receiverType);
        // ifreceivesidenongroup,thencreate senderUserId and receiverUserId conversation.
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

        // 3.groupinstallparameter,call sendMessageToAgent method
        $aiSeqDTO->getExtra() === null && $aiSeqDTO->setExtra(new SeqExtra());
        $aiSeqDTO->getExtra()->setTopicId($topicId);
        $aiSeqDTO->setConversationId($senderConversationEntity->getId());
        return $this->sendMessageToAgent($aiSeqDTO, $appMessageId, $sendTime, $doNotParseReferMessageId);
    }

    /**
     * assistantgivepersoncategoryorgrouphairmessage,supportonlinemessageandofflinemessage(depend onatuserwhetheronline).
     * @param DelightfulSeqEntity $aiSeqDTO how to pass parameterscanreference apilayer aiSendMessage method
     * @param string $appMessageId messageprevent duplicate,customerclient(includeflow)fromselftomessagegenerateoneitemencoding
     * @param bool $doNotParseReferMessageId notby chat judge referMessageId quoteo clockmachine,bycallsidefromselfjudge
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
            // ifusergiveassistantsendmultipleitemmessage,assistantreplyo clock,needletuserawareassistantreplyishewhichitemmessage.
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
                // nonstreamresponseorpersonstreamresponsestartinput
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
     * such asaccording tohairitemsideseq,forreceiveitemsidegenerateseq,deliverseq.
     * @throws Throwable
     */
    public function asyncHandlerChatMessage(DelightfulSeqEntity $senderSeqEntity): void
    {
        Db::beginTransaction();
        try {
            # bydownischatmessage. adopt write diffusion:ifisgroup,thenforgroupmembereachpersongenerateseq
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
                    // todo groupwithinsurfacetopicmessagealsowrite topic_messages tablemiddle
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
        // conversation havepermissionvalidation
        $this->checkConversationsOwnership($userAuthorization, [$conversationId]);

        // bytimerange,getconversation/topicmessage
        $clientSeqList = $this->delightfulChatDomainService->getConversationChatMessages($conversationId, $conversationMessagesQueryDTO);
        return $this->formatConversationMessagesReturn($clientSeqList, $conversationMessagesQueryDTO);
    }

    /**
     * @deprecated
     */
    public function getMessageByConversationIds(DelightfulUserAuthorization $userAuthorization, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        // conversation havepermissionvalidation
        $conversationIds = $conversationMessagesQueryDTO->getConversationIds();
        if (! empty($conversationIds)) {
            $this->checkConversationsOwnership($userAuthorization, $conversationIds);
        }

        // getconversationmessage(notice,featureitemandgetMessagesByConversationIddifferent)
        $clientSeqList = $this->delightfulChatDomainService->getConversationsChatMessages($conversationMessagesQueryDTO);
        return $this->formatConversationMessagesReturn($clientSeqList, $conversationMessagesQueryDTO);
    }

    // byconversation id groupgetseveralitemmostnewmessage
    public function getConversationsMessagesGroupById(DelightfulUserAuthorization $userAuthorization, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        // conversation havepermissionvalidation
        $conversationIds = $conversationMessagesQueryDTO->getConversationIds();
        if (! empty($conversationIds)) {
            $this->checkConversationsOwnership($userAuthorization, $conversationIds);
        }

        $clientSeqList = $this->delightfulChatDomainService->getConversationsMessagesGroupById($conversationMessagesQueryDTO);
        // byconversation id group,return
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
        youisoneprofessionalcontenttitlegeneratehelphand.pleasestrictaccording tobydownrequireforconversationcontentgeneratetitle:

        ## taskgoal
        according toconversationcontent,generateoneconcise,accuratetitle,cansummarizeconversationcorecoretheme.

        ## themepriorityleveloriginalthen
        whenconversationinvolveandmultipledifferentthemeo clock:
        1. priorityclosenoteconversationmiddlemostbackdiscussiontheme(mostnewtopic)
        2. bymostnearconversationcontentformainreferencebasis
        3. ifmostbackthemediscussionmorefor充minute,thenbythisasfortitlecorecore
        4. ignoreearlyalreadyalreadyendtopic,unlessit们andmostnewtopicclosely relatedclose

        ## strictrequire
        1. titlelength:notexceedspass 15 character.Englishoneletter算onecharacter,Chinese charactersonecharacter countonecharacter,otherlanguagetypecollectuseanalogouscountsolution.
        2. contentrelatedclose:titlemustdirectlyreflectconversationcorecoretheme
        3. languagestyle:usestatementpropertylanguagesentence,avoidquestionsentence
        4. outputformat:onlyoutputtitlecontent,notwantaddanyexplain,markpointorothertext
        5. forbidlinefor:notwantreturnanswerconversationmiddleissue,notwantconductquotaoutsideexplain

        ## conversationcontent
        <CONVERSATION_START>
        {textContent}
        <CONVERSATION_END>

        ## outputlanguage
        <LANGUAGE_START>
        pleaseuse{language}languageoutputcontent
        <LANGUAGE_END>

        ## output
        pleasedirectlyoutputtitle:
        PROMPT;

        $prompt = str_replace(['{language}', '{textContent}'], [$language, $textContent], $prompt);

        $conversationId = uniqid('', true);
        $messageHistory = new MessageHistory();
        $messageHistory->addMessages(new SystemMessage($prompt), $conversationId);
        return $this->getSummaryFromLLM($authorization, $messageHistory, $conversationId);
    }

    /**
     * usebigmodeltotextconductsummary(usecustomizehintword).
     *
     * @param DelightfulUserAuthorization $authorization userauthorization
     * @param string $customPrompt completecustomizehintword(notmakeanyreplacehandle)
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
        // permissionvalidation,judgeusermessagemiddle,whethercontainthistimehethinkdownloadfile
        $fileEntities = $this->delightfulChatFileDomainService->checkAndGetFilePaths($fileDTOs, $dataIsolation);
        // downloado clockalsooriginalfileoriginalname
        $downloadNames = [];
        $fileDownloadUrls = [];
        $filePaths = [];
        foreach ($fileEntities as $fileEntity) {
            // filterdrophaveoutsidechain,butisnot file_key
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
     * givehairitemsidegeneratemessageandSeq.forguaranteesystemstableproperty,givereceiveitemsidegeneratemessageandSeqstepputinmqasyncgomake.
     * !!! notice,transactionmiddledeliver mq,maybetransactionalsonotsubmit,mqmessagethenalreadybeconsume.
     * @throws Throwable
     */
    public function delightfulChat(
        DelightfulSeqEntity $senderSeqDTO,
        DelightfulMessageEntity $senderMessageDTO,
        DelightfulConversationEntity $senderConversationEntity
    ): array {
        // givehairitemsidegeneratemessageandSeq
        // frommessageStructmiddleparseoutcomeconversationwindowdetail
        $receiveType = $senderConversationEntity->getReceiveType();
        if (! in_array($receiveType, [ConversationType::Ai, ConversationType::User, ConversationType::Group], true)) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_TYPE_ERROR);
        }

        $language = CoContext::getLanguage();
        // auditrequirement:ifiseditmessage,writemessageversiontable,andupdateoriginalmessageversion_id
        $extra = $senderSeqDTO->getExtra();
        // settinglanguageinfo
        $editMessageOptions = $extra?->getEditMessageOptions();
        if ($extra !== null && $editMessageOptions !== null && ! empty($editMessageOptions->getDelightfulMessageId())) {
            $senderMessageDTO->setDelightfulMessageId($editMessageOptions->getDelightfulMessageId());
            $messageVersionEntity = $this->delightfulChatDomainService->editMessage($senderMessageDTO);
            $editMessageOptions->setMessageVersionId($messageVersionEntity->getVersionId());
            $senderSeqDTO->setExtra($extra->setEditMessageOptions($editMessageOptions));
            // againcheckonetime $messageEntity ,avoidduplicatecreate
            $messageEntity = $this->delightfulChatDomainService->getMessageByDelightfulMessageId($senderMessageDTO->getDelightfulMessageId());
            $messageEntity && $messageEntity->setLanguage($language);
        }

        // ifquotemessagebeeditpass,thatwhatmodify referMessageId fororiginalmessage id
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
            // willmessagestreamreturngivecurrentcustomerclient! butisalsoiswillasyncpushgiveuser haveonlinecustomerclient.
            return SeqAssembler::getClientSeqStruct($senderSeqEntity, $messageEntity)->toArray();
        }

        # nonstreammessage
        try {
            Db::beginTransaction();
            if (! isset($messageEntity)) {
                $messageEntity = $this->delightfulChatDomainService->createDelightfulMessageByAppClient($senderMessageDTO, $senderConversationEntity);
            }
            // givefromselfmessagestreamgeneratesequencecolumn,andcertainmessagereceivepersoncolumntable
            $senderSeqEntity = $this->delightfulChatDomainService->generateSenderSequenceByChatMessage($senderSeqDTO, $messageEntity, $senderConversationEntity);
            // avoid seq tablecarrytoomultiplefeature,addtoomultipleindex,thereforewilltopicmessagesingleuniquewriteto topic_messages tablemiddle
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
                // forguaranteereceivehairdoublesidemessageorderonetoproperty,ifisprivate chat,thensyncgenerate seq
                $receiveSeqEntity = $this->syncHandlerSingleChatMessage($senderSeqEntity, $messageEntity);
            } elseif ($conversationType === ConversationType::Group) {
                // group chatetcscenarioasyncgivereceiveitemsidegenerateSeqandpushgivereceiveitemside
                $this->delightfulChatDomainService->dispatchSeq($senderChatSeqCreatedEvent);
            } else {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_TYPE_ERROR);
            }
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            throw $exception;
        }
        // use mq pushmessagegivereceiveitemside
        isset($receiveSeqEntity) && $this->pushReceiveChatSequence($messageEntity, $receiveSeqEntity);
        // asyncpushmessagegivefromselfotherdevice
        if ($messageEntity->getSenderType() !== ConversationType::Ai) {
            co(function () use ($senderChatSeqCreatedEvent) {
                $this->delightfulChatDomainService->pushChatSequence($senderChatSeqCreatedEvent);
            });
        }

        // ifiseditmessage,andisusereditassistanthaircomeapprovalformo clock,returnnullarray.
        // factorforthiso clockcreate seq_id isassistant,notisuser,returnwillcreatebecometrouble.
        // alreadyby mq minutehairmessageback,userwillasyncreceivetobelongathefromselfmessagepush.
        if (isset($editMessageOptions) && ! empty($editMessageOptions->getDelightfulMessageId())
            && $messageEntity->getSenderId() !== $senderMessageDTO->getSenderId()) {
            return [];
        }

        // willmessagestreamreturngivecurrentcustomerclient! butisalsoiswillasyncpushgiveuser haveonlinecustomerclient.
        return SeqAssembler::getClientSeqStruct($senderSeqEntity, $messageEntity)->toArray();
    }

    /**
     * ifquotemessagebeeditpass,thatwhatmodify referMessageId fororiginalmessage id.
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
        // convenientatfrontclient rendering,updatequotemessageIDfororiginalmessageID
        $senderSeqDTO->setReferMessageId($delightfulSeqEntity->getMessageId());
    }

    /**
     * openhairlevelsegment,frontclienttoconnecthavetimedifference,updowntextcompatiblepropertyhandle.
     */
    public function setUserContext(string $userToken, ?DelightfulContext $delightfulContext): void
    {
        if (! $delightfulContext) {
            ExceptionBuilder::throw(ChatErrorCode::CONTEXT_LOST);
        }
        // forsupportonewschainreceivehairmultiple accountsnumbermessage,allowinmessageupdowntextmiddlepass inaccountnumber token
        if (! $delightfulContext->getAuthorization()) {
            $delightfulContext->setAuthorization($userToken);
        }
        // coroutineupdowntextmiddlesettinguserinfo,supply WebsocketChatUserGuard use
        WebSocketContext::set(DelightfulContext::class, $delightfulContext);
    }

    /**
     * chatwindowhitfieldo clock补alluserinput.foradaptgroup chat,thiswithin role itsactualisusernickname,whilenotisroletype.
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
        // gettopicmostnear 20 itemconversationrecord
        $clientSeqResponseDTOS = $this->delightfulChatDomainService->getConversationChatMessages($conversationId, $conversationMessagesQueryDTO);
        // getreceivehairdoublesideuserinfo,useat补allo clockenhanceroletype
        $userIds = [];
        foreach ($clientSeqResponseDTOS as $clientSeqResponseDTO) {
            // receivecollection user_id
            $userIds[] = $clientSeqResponseDTO->getSeq()->getMessage()->getSenderId();
        }
        // fromself user_id alsoaddentergo
        $userIds[] = $userAuthorization->getId();
        // goreload
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
            // temporaryo clockonlyhandleuserinput,byandcangetpuretextmessagetype
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
     * forguaranteereceivehairdoublesidemessageorderonetoproperty,ifisprivate chat,thensyncgenerate seq.
     * @throws Throwable
     */
    private function syncHandlerSingleChatMessage(DelightfulSeqEntity $senderSeqEntity, DelightfulMessageEntity $senderMessageEntity): DelightfulSeqEntity
    {
        $delightfulSeqStatus = DelightfulMessageStatus::Unread;
        # assistantmaybe参andprivate chat/group chatetcscenario,readmemoryo clock,needreadfromselfconversationwindowdownmessage.
        $receiveSeqEntity = $this->delightfulChatDomainService->generateReceiveSequenceByChatMessage($senderSeqEntity, $senderMessageEntity, $delightfulSeqStatus);
        // avoid seq tablecarrytoomultiplefeature,addtoomultipleindex,thereforewilltopicmessagesingleuniquewriteto topic_messages tablemiddle
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
        // iftitlelengthexceedspass20characterthenbacksurfaceuse...replace
        if (mb_strlen($choiceContent) > 20) {
            $choiceContent = mb_substr($choiceContent, 0, 20) . '...';
        }

        return $choiceContent;
    }

    private function getMessageTextContent(MessageInterface $message): string
    {
        // temporaryo clockonlyhandleuserinput,byandcangetpuretextmessagetype
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
        // according to $order indatabasemiddlequery,butistoreturnresultcollectiondescendingrowcolumn.
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
        // typeandcontentgroupcombineinoneuponlyisonecanusemessagetype
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
     * according tocustomerclienthaircomechatmessagetype,minutehairtotoshouldhandlemodepiece.
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
            // securitypropertyguarantee,validationattachmentmiddlefilewhetherbelongatcurrentuser
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
     * validationattachmentmiddlefilewhetherbelongatcurrentuser,andpopulateattachmentinfo.(filename/typeetcfield).
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
     * checkconversation havepermission
     * ensure haveconversationIDallbelongatcurrentaccountnumber,nothenthrowexception.
     *
     * @param DelightfulUserAuthorization $userAuthorization userauthorizationinfo
     * @param array $conversationIds pendingcheckconversationIDarray
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

        // receivecollection haveconversationassociateuserID
        $userIds = [];
        foreach ($conversations as $conversation) {
            $userIds[] = $conversation->getUserId();
        }
        $userIds = array_unique($userIds);

        // batchquantitygetuserinfo
        $userEntities = $this->delightfulUserDomainService->getUserByIdsWithoutOrganization($userIds);
        $userMap = array_column($userEntities, 'delightful_id', 'user_id');

        // checkeachconversationwhetherbelongatcurrentuser(passdelightful_idmatch)
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
     * tospecifictypemessageconductbusinessrulevalidation.
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

        // reloadnewgetpopulatebackattachment
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
