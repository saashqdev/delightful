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
 * chatmessage相关.
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
        // 将所have sid all加入toroom id value为 delightfulId 的roommiddle
        $this->delightfulChatDomainService->joinRoom($userAuthorization->getDelightfulId(), $socket);
    }

    /**
     * returnmost大message的倒数 n item序column.
     * @return ClientSequenceResponse[]
     * @deprecated
     */
    public function pullMessage(DelightfulUserAuthorization $userAuthorization, array $params): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulChatDomainService->pullMessage($dataIsolation, $params);
    }

    /**
     * returnmost大message的倒数 n item序column.
     * @return ClientSequenceResponse[]
     */
    public function pullByPageToken(DelightfulUserAuthorization $userAuthorization, array $params): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $pageSize = 200;
        return $this->delightfulChatDomainService->pullByPageToken($dataIsolation, $params, $pageSize);
    }

    /**
     * returnmost大message的倒数 n item序column.
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
        // whether是editmessage
        $editMessageOptions = $chatRequest->getData()->getEditMessageOptions();
        if ($editMessageOptions !== null) {
            $seqExtra->setEditMessageOptions($editMessageOptions);
        }
        // seq 的extensioninfo. ifneed检索话题的message,请query topic_messages 表
        $topicId && $seqExtra->setTopicId($topicId);
        $seqDTO->setExtra($seqExtra);
        // if是跟assistant的private chat，andnothave话题 id，自动create一话题
        if ($conversationEntity->getReceiveType() === ConversationType::Ai && empty($seqDTO->getExtra()?->getTopicId())) {
            $topicId = $this->delightfulTopicDomainService->agentSendMessageGetTopicId($conversationEntity, 0);
            // not影响原have逻辑，将 topicId settingto extra middle
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
     * message鉴权.
     * @throws Throwable
     */
    public function checkSendMessageAuth(DelightfulSeqEntity $senderSeqDTO, DelightfulMessageEntity $senderMessageDTO, DelightfulConversationEntity $conversationEntity, DataIsolation $dataIsolation): void
    {
        // checkconversation id所属organization，与current传入organizationencoding的一致property
        if ($conversationEntity->getUserOrganizationCode() !== $dataIsolation->getCurrentOrganizationCode()) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // 判断conversation的hair起者whether是currentuser,并andnot是assistant
        if ($conversationEntity->getReceiveType() !== ConversationType::Ai && $conversationEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // conversationwhether已bedelete
        if ($conversationEntity->getStatus() === ConversationStatus::Delete) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_DELETED);
        }
        // if是editmessage，checkbeeditmessage的legalproperty(自己hair的message，andincurrentconversationmiddle)
        $this->checkEditMessageLegality($senderSeqDTO, $dataIsolation);
        return;
        // todo ifmessagemiddlehavefile:1.判断file的所have者whether是currentuser;2.判断userwhetherreceive过这些file。
        /* @phpstan-ignore-next-line */
        $messageContent = $senderMessageDTO->getContent();
        if ($messageContent instanceof ChatFileInterface) {
            $fileIds = $messageContent->getFileIds();
            if (! empty($fileIds)) {
                // 批quantityqueryfile所have权，而not是循环query
                $fileEntities = $this->delightfulChatFileDomainService->getFileEntitiesByFileIds($fileIds);

                // checkwhether所havefileall存in
                $existingFileIds = array_map(static function (DelightfulChatFileEntity $fileEntity) {
                    return $fileEntity->getFileId();
                }, $fileEntities);

                // checkwhetherhaverequest的file ID notin已queryto的file ID middle
                $missingFileIds = array_diff($fileIds, $existingFileIds);
                if (! empty($missingFileIds)) {
                    ExceptionBuilder::throw(ChatErrorCode::FILE_NOT_FOUND);
                }

                // checkfile所have者whether是currentuser
                foreach ($fileEntities as $fileEntity) {
                    if ($fileEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
                        ExceptionBuilder::throw(ChatErrorCode::FILE_NOT_FOUND);
                    }
                }
            }
        }

        // todo checkwhetherhavehairmessage的permission(needhave好友关系，企业关系，集团关系，合作伙伴关系etc)
    }

    /**
     * assistant给personcategoryor者群hairmessage,supportonlinemessage和offlinemessage(取决atuserwhetheronline).
     * @param DelightfulSeqEntity $aiSeqDTO 怎么传参can参考 apilayer的 aiSendMessage method
     * @param string $appMessageId message防重,customer端(includeflow)自己对messagegenerate一itemencoding
     * @param bool $doNotParseReferMessageId not由 chat 判断 referMessageId 的quoteo clock机,由call方自己判断
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
            // ifuser给assistantsend了多itemmessage,assistantreplyo clock,need让user知晓assistantreplyis他的哪itemmessage.
            $aiSeqDTO = $this->delightfulChatDomainService->aiReferMessage($aiSeqDTO, $doNotParseReferMessageId);
            // getassistant的conversation窗口
            $aiConversationEntity = $this->delightfulChatDomainService->getConversationById($aiSeqDTO->getConversationId());
            if ($aiConversationEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
            }
            // confirmhairitempersonwhether是assistant
            $aiUserId = $aiConversationEntity->getUserId();
            $aiUserEntity = $this->delightfulChatDomainService->getUserInfo($aiUserId);
            if ($aiUserEntity->getUserType() !== UserType::Ai) {
                ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
            }
            // if是assistant与personprivate chat，andassistantsend的messagenothave话题 id，then报错
            if ($aiConversationEntity->getReceiveType() === ConversationType::User && empty($aiSeqDTO->getExtra()?->getTopicId())) {
                ExceptionBuilder::throw(ChatErrorCode::TOPIC_ID_NOT_FOUND);
            }
            // assistant准备starthairmessage了,endinputstatus
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
     * assistant给personcategoryor者群hairmessage,cannot传conversation和话题 id,自动createconversation，nongroupconversation自动适配话题 id.
     * @param string $appMessageId message防重,customer端(includeflow)自己对messagegenerate一itemencoding
     * @param bool $doNotParseReferMessageId cannot由 chat 判断 referMessageId 的quoteo clock机,由call方自己判断
     * @throws Throwable
     */
    public function agentSendMessage(
        DelightfulSeqEntity $aiSeqDTO,
        string $senderUserId,
        string $receiverId,
        string $appMessageId = '',
        bool $doNotParseReferMessageId = false,// cannot由 chat 判断 referMessageId 的quoteo clock机,由call方自己判断
        ?Carbon $sendTime = null,
        ?ConversationType $receiverType = null
    ): array {
        // 1.判断 $senderUserId 与 $receiverUserId的conversationwhether存in（参考getOrCreateConversationmethod）
        $senderConversationEntity = $this->delightfulConversationDomainService->getOrCreateConversation($senderUserId, $receiverId, $receiverType);
        // also要createreceive方的conversation窗口，要not然无法create话题
        $this->delightfulConversationDomainService->getOrCreateConversation($receiverId, $senderUserId);

        // 2.if $seqExtra not为 null，校验whetherhave topic id，ifnothave，参考 agentSendMessageGetTopicId method，得to话题 id
        $topicId = $aiSeqDTO->getExtra()?->getTopicId() ?? '';
        if (empty($topicId) && $receiverType !== ConversationType::Group) {
            $topicId = $this->delightfulTopicDomainService->agentSendMessageGetTopicId($senderConversationEntity, 0);
        }
        // 3.group装parameter，call aiSendMessage method
        $aiSeqDTO->getExtra() === null && $aiSeqDTO->setExtra(new SeqExtra());
        $aiSeqDTO->getExtra()->setTopicId($topicId);
        $aiSeqDTO->setConversationId($senderConversationEntity->getId());
        return $this->aiSendMessage($aiSeqDTO, $appMessageId, $sendTime, $doNotParseReferMessageId);
    }

    /**
     * personcategory给assistantor者群hairmessage,cannot传conversation和话题 id,自动createconversation，nongroupconversation自动适配话题 id.
     * @param string $appMessageId message防重,customer端(includeflow)自己对messagegenerate一itemencoding
     * @param bool $doNotParseReferMessageId cannot由 chat 判断 referMessageId 的quoteo clock机,由call方自己判断
     * @throws Throwable
     */
    public function userSendMessageToAgent(
        DelightfulSeqEntity $aiSeqDTO,
        string $senderUserId,
        string $receiverId,
        string $appMessageId = '',
        bool $doNotParseReferMessageId = false,// cannot由 chat 判断 referMessageId 的quoteo clock机,由call方自己判断
        ?Carbon $sendTime = null,
        ?ConversationType $receiverType = null,
        string $topicId = ''
    ): array {
        // 1.判断 $senderUserId 与 $receiverUserId的conversationwhether存in（参考getOrCreateConversationmethod）
        $senderConversationEntity = $this->delightfulConversationDomainService->getOrCreateConversation($senderUserId, $receiverId, $receiverType);
        // ifreceive方nongroup，thencreate senderUserId 与 receiverUserId 的conversation.
        if ($receiverType !== ConversationType::Group) {
            $this->delightfulConversationDomainService->getOrCreateConversation($receiverId, $senderUserId);
        }
        // 2.if $seqExtra not为 null，校验whetherhave topic id，ifnothave，参考 agentSendMessageGetTopicId method，得to话题 id
        if (empty($topicId)) {
            $topicId = $aiSeqDTO->getExtra()?->getTopicId() ?? '';
        }

        if (empty($topicId) && $receiverType !== ConversationType::Group) {
            $topicId = $this->delightfulTopicDomainService->agentSendMessageGetTopicId($senderConversationEntity, 0);
        }

        // if是group，thennotneedget话题 id
        if ($receiverType === ConversationType::Group) {
            $topicId = '';
        }

        // 3.group装parameter，call sendMessageToAgent method
        $aiSeqDTO->getExtra() === null && $aiSeqDTO->setExtra(new SeqExtra());
        $aiSeqDTO->getExtra()->setTopicId($topicId);
        $aiSeqDTO->setConversationId($senderConversationEntity->getId());
        return $this->sendMessageToAgent($aiSeqDTO, $appMessageId, $sendTime, $doNotParseReferMessageId);
    }

    /**
     * assistant给personcategoryor者群hairmessage,supportonlinemessage和offlinemessage(取决atuserwhetheronline).
     * @param DelightfulSeqEntity $aiSeqDTO 怎么传参can参考 apilayer的 aiSendMessage method
     * @param string $appMessageId message防重,customer端(includeflow)自己对messagegenerate一itemencoding
     * @param bool $doNotParseReferMessageId not由 chat 判断 referMessageId 的quoteo clock机,由call方自己判断
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
            // ifuser给assistantsend了多itemmessage,assistantreplyo clock,need让user知晓assistantreplyis他的哪itemmessage.
            $aiSeqDTO = $this->delightfulChatDomainService->aiReferMessage($aiSeqDTO, $doNotParseReferMessageId);
            // getassistant的conversation窗口
            $aiConversationEntity = $this->delightfulChatDomainService->getConversationById($aiSeqDTO->getConversationId());
            if ($aiConversationEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
            }
            // confirmhairitempersonwhether是assistant
            $aiUserId = $aiConversationEntity->getUserId();
            $aiUserEntity = $this->delightfulChatDomainService->getUserInfo($aiUserId);
            // if ($aiUserEntity->getUserType() !== UserType::Ai) {
            //     ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
            // }
            // if是assistant与personprivate chat，andassistantsend的messagenothave话题 id，then报错
            if ($aiConversationEntity->getReceiveType() === ConversationType::User && empty($aiSeqDTO->getExtra()?->getTopicId())) {
                ExceptionBuilder::throw(ChatErrorCode::TOPIC_ID_NOT_FOUND);
            }
            // assistant准备starthairmessage了,endinputstatus
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
     * minutehairasyncmessagequeuemiddle的seq.
     * such asaccording tohairitem方的seq,为收item方generateseq,投递seq.
     * @throws Throwable
     */
    public function asyncHandlerChatMessage(DelightfulSeqEntity $senderSeqEntity): void
    {
        Db::beginTransaction();
        try {
            # bydown是chatmessage. 采取写扩散:if是群,then为群member的eachpersongenerateseq
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
                    // todo 群withinsurface的话题messagealsowrite topic_messages 表middle
                    // 将这些 seq_id merge为一item mq messageconductpush/消费
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
     * conversation窗口scrollloadmessage.
     */
    public function getMessagesByConversationId(DelightfulUserAuthorization $userAuthorization, string $conversationId, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        // conversation所have权校验
        $this->checkConversationsOwnership($userAuthorization, [$conversationId]);

        // 按timerange，getconversation/话题的message
        $clientSeqList = $this->delightfulChatDomainService->getConversationChatMessages($conversationId, $conversationMessagesQueryDTO);
        return $this->formatConversationMessagesReturn($clientSeqList, $conversationMessagesQueryDTO);
    }

    /**
     * @deprecated
     */
    public function getMessageByConversationIds(DelightfulUserAuthorization $userAuthorization, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        // conversation所have权校验
        $conversationIds = $conversationMessagesQueryDTO->getConversationIds();
        if (! empty($conversationIds)) {
            $this->checkConversationsOwnership($userAuthorization, $conversationIds);
        }

        // getconversation的message（注意，feature目的与getMessagesByConversationIddifferent）
        $clientSeqList = $this->delightfulChatDomainService->getConversationsChatMessages($conversationMessagesQueryDTO);
        return $this->formatConversationMessagesReturn($clientSeqList, $conversationMessagesQueryDTO);
    }

    // 按conversation id groupget几itemmost新message
    public function getConversationsMessagesGroupById(DelightfulUserAuthorization $userAuthorization, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        // conversation所have权校验
        $conversationIds = $conversationMessagesQueryDTO->getConversationIds();
        if (! empty($conversationIds)) {
            $this->checkConversationsOwnership($userAuthorization, $conversationIds);
        }

        $clientSeqList = $this->delightfulChatDomainService->getConversationsMessagesGroupById($conversationMessagesQueryDTO);
        // 按conversation id group，return
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
     * use大model对textconduct总结.
     */
    public function summarizeText(DelightfulUserAuthorization $authorization, string $textContent, string $language = 'zh_CN'): string
    {
        if (empty($textContent)) {
            return '';
        }
        $prompt = <<<'PROMPT'
        你是一专业的contenttitlegenerate助hand。请严格按照bydown要求为conversationcontentgeneratetitle：

        ## taskgoal
        according toconversationcontent，generate一简洁、准确的title，can概括conversation的核coretheme。

        ## theme优先level原then
        whenconversation涉及多differentthemeo clock：
        1. 优先关注conversationmiddlemostbackdiscussion的theme（mostnew话题）
        2. bymost近的conversationcontent为main参考依据
        3. ifmostback的themediscussionmore为充minute，thenby此作为title的核core
        4. ignore早期已经end的话题，unless它们与most新话题密切相关

        ## 严格要求
        1. titlelength：not超过 15 character。English一字母算一character，汉字一字算一character，其他语type采useanalogouscountsolution。
        2. content相关：titlemust直接反映conversation的核coretheme
        3. languagestyle：use陈述property语sentence，避免疑问sentence
        4. outputformat：只outputtitlecontent，not要add任何解释、标pointor其他text
        5. forbidline为：not要回答conversationmiddle的issue，not要conduct额outside解释

        ## conversationcontent
        <CONVERSATION_START>
        {textContent}
        <CONVERSATION_END>

        ## outputlanguage
        <LANGUAGE_START>
        请use{language}languageoutputcontent
        <LANGUAGE_END>

        ## output
        请直接outputtitle：
        PROMPT;

        $prompt = str_replace(['{language}', '{textContent}'], [$language, $textContent], $prompt);

        $conversationId = uniqid('', true);
        $messageHistory = new MessageHistory();
        $messageHistory->addMessages(new SystemMessage($prompt), $conversationId);
        return $this->getSummaryFromLLM($authorization, $messageHistory, $conversationId);
    }

    /**
     * use大model对textconduct总结（usecustomizehint词）.
     *
     * @param DelightfulUserAuthorization $authorization userauthorization
     * @param string $customPrompt 完整的customizehint词（not做任何替换handle）
     * @return string generate的title
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
        // permission校验，判断user的messagemiddle，whethercontain本time他想download的file
        $fileEntities = $this->delightfulChatFileDomainService->checkAndGetFilePaths($fileDTOs, $dataIsolation);
        // downloado clockalso原file原本的name
        $downloadNames = [];
        $fileDownloadUrls = [];
        $filePaths = [];
        foreach ($fileEntities as $fileEntity) {
            // filter掉haveoutside链，but是not file_key
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
     * 给hairitem方generatemessage和Seq.为了保证systemstableproperty,给收item方generatemessage和Seq的step放inmqasync去做.
     * !!! 注意,transactionmiddle投递 mq,可能transactionalsonotsubmit,mqmessagethen已be消费.
     * @throws Throwable
     */
    public function delightfulChat(
        DelightfulSeqEntity $senderSeqDTO,
        DelightfulMessageEntity $senderMessageDTO,
        DelightfulConversationEntity $senderConversationEntity
    ): array {
        // 给hairitem方generatemessage和Seq
        // frommessageStructmiddleparse出来conversation窗口detail
        $receiveType = $senderConversationEntity->getReceiveType();
        if (! in_array($receiveType, [ConversationType::Ai, ConversationType::User, ConversationType::Group], true)) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_TYPE_ERROR);
        }

        $language = CoContext::getLanguage();
        // 审计需求：if是editmessage，writemessageversion表，并update原message的version_id
        $extra = $senderSeqDTO->getExtra();
        // settinglanguageinfo
        $editMessageOptions = $extra?->getEditMessageOptions();
        if ($extra !== null && $editMessageOptions !== null && ! empty($editMessageOptions->getDelightfulMessageId())) {
            $senderMessageDTO->setDelightfulMessageId($editMessageOptions->getDelightfulMessageId());
            $messageVersionEntity = $this->delightfulChatDomainService->editMessage($senderMessageDTO);
            $editMessageOptions->setMessageVersionId($messageVersionEntity->getVersionId());
            $senderSeqDTO->setExtra($extra->setEditMessageOptions($editMessageOptions));
            // again查一time $messageEntity ，避免重复create
            $messageEntity = $this->delightfulChatDomainService->getMessageByDelightfulMessageId($senderMessageDTO->getDelightfulMessageId());
            $messageEntity && $messageEntity->setLanguage($language);
        }

        // ifquote的messagebeedit过，那么modify referMessageId 为original的message id
        $this->checkAndUpdateReferMessageId($senderSeqDTO);

        $senderMessageDTO->setLanguage($language);

        $messageStruct = $senderMessageDTO->getContent();
        if ($messageStruct instanceof StreamMessageInterface && $messageStruct->isStream()) {
            // streammessage的场景
            if ($messageStruct->getStreamOptions()->getStatus() === StreamMessageStatus::Start) {
                // if是start，call createAndSendStreamStartSequence method
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
            // 只in确定 $senderSeqEntity 和 $messageEntity，useatreturndata结构
            $senderSeqEntity = $this->delightfulSeqDomainService->getSeqEntityByMessageId($senderMessageId);
            $messageEntity = $this->delightfulChatDomainService->getMessageByDelightfulMessageId($delightfulMessageId);
            // 将messagestreamreturn给currentcustomer端! but是also是willasyncpush给user的所haveonlinecustomer端.
            return SeqAssembler::getClientSeqStruct($senderSeqEntity, $messageEntity)->toArray();
        }

        # nonstreammessage
        try {
            Db::beginTransaction();
            if (! isset($messageEntity)) {
                $messageEntity = $this->delightfulChatDomainService->createDelightfulMessageByAppClient($senderMessageDTO, $senderConversationEntity);
            }
            // 给自己的messagestreamgenerate序column,并确定message的receivepersoncolumn表
            $senderSeqEntity = $this->delightfulChatDomainService->generateSenderSequenceByChatMessage($senderSeqDTO, $messageEntity, $senderConversationEntity);
            // 避免 seq 表承载too多feature,加too多索引,therefore将话题的message单独writeto topic_messages 表middle
            $this->delightfulChatDomainService->createTopicMessage($senderSeqEntity);
            // 确定message优先level
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
                // 为了保证收hair双方的message顺序一致property，if是private chat，thensyncgenerate seq
                $receiveSeqEntity = $this->syncHandlerSingleChatMessage($senderSeqEntity, $messageEntity);
            } elseif ($conversationType === ConversationType::Group) {
                // group chatetc场景async给收item方generateSeq并push给收item方
                $this->delightfulChatDomainService->dispatchSeq($senderChatSeqCreatedEvent);
            } else {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_TYPE_ERROR);
            }
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            throw $exception;
        }
        // use mq pushmessage给收item方
        isset($receiveSeqEntity) && $this->pushReceiveChatSequence($messageEntity, $receiveSeqEntity);
        // asyncpushmessage给自己的其他设备
        if ($messageEntity->getSenderType() !== ConversationType::Ai) {
            co(function () use ($senderChatSeqCreatedEvent) {
                $this->delightfulChatDomainService->pushChatSequence($senderChatSeqCreatedEvent);
            });
        }

        // if是editmessage，and是useredit了assistanthair来的approvalformo clock，returnnullarray。
        // 因为此o clockcreate的 seq_id 是assistant的，not是user的，returnwill造become困扰。
        // 经由 mq minutehairmessageback，userwillasync收to属at他自己的messagepush。
        if (isset($editMessageOptions) && ! empty($editMessageOptions->getDelightfulMessageId())
            && $messageEntity->getSenderId() !== $senderMessageDTO->getSenderId()) {
            return [];
        }

        // 将messagestreamreturn给currentcustomer端! but是also是willasyncpush给user的所haveonlinecustomer端.
        return SeqAssembler::getClientSeqStruct($senderSeqEntity, $messageEntity)->toArray();
    }

    /**
     * ifquote的messagebeedit过，那么modify referMessageId 为original的message id.
     */
    public function checkAndUpdateReferMessageId(DelightfulSeqEntity $senderSeqDTO): void
    {
        // getquotemessage的ID
        $referMessageId = $senderSeqDTO->getReferMessageId();
        if (empty($referMessageId)) {
            return;
        }

        // querybequote的message
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
        // 便atfront端渲染，updatequotemessageID为originalmessageID
        $senderSeqDTO->setReferMessageId($delightfulSeqEntity->getMessageId());
    }

    /**
     * 开hair阶segment,front端对接havetime差,updown文compatiblepropertyhandle.
     */
    public function setUserContext(string $userToken, ?DelightfulContext $delightfulContext): void
    {
        if (! $delightfulContext) {
            ExceptionBuilder::throw(ChatErrorCode::CONTEXT_LOST);
        }
        // 为了support一ws链receivehair多账number的message,allowinmessageupdown文middle传入账number token
        if (! $delightfulContext->getAuthorization()) {
            $delightfulContext->setAuthorization($userToken);
        }
        // 协程updown文middlesettinguserinfo,供 WebsocketChatUserGuard use
        WebSocketContext::set(DelightfulContext::class, $delightfulContext);
    }

    /**
     * chat窗口打字o clock补alluserinput。为了适配group chat，这within的 role 其实是user的nickname，而not是roletype。
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
        // get话题的most近 20 itemconversationrecord
        $clientSeqResponseDTOS = $this->delightfulChatDomainService->getConversationChatMessages($conversationId, $conversationMessagesQueryDTO);
        // get收hair双方的userinfo，useat补allo clockenhanceroletype
        $userIds = [];
        foreach ($clientSeqResponseDTOS as $clientSeqResponseDTO) {
            // 收集 user_id
            $userIds[] = $clientSeqResponseDTO->getSeq()->getMessage()->getSenderId();
        }
        // 把自己的 user_id also加进去
        $userIds[] = $userAuthorization->getId();
        // 去重
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
            // 暂o clock只handleuser的input，by及能get纯text的messagetype
            $messageContent = $this->getMessageTextContent($message);
            if (empty($messageContent)) {
                continue;
            }

            // according toparameter决定usenicknamealso是传统的 role
            if ($useNicknameAsRole) {
                $userMessages[$clientSeqResponseDTO->getSeq()->getSeqId()] = [
                    'role' => $delightfulUserEntity->getNickname(),
                    'role_description' => $delightfulUserEntity->getDescription(),
                    'content' => $messageContent,
                ];
            } else {
                // use传统的 role，判断whether为 AI user
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
        // according to seq_id 升序rowcolumn
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
     * 为了保证收hair双方的message顺序一致property，if是private chat，thensyncgenerate seq.
     * @throws Throwable
     */
    private function syncHandlerSingleChatMessage(DelightfulSeqEntity $senderSeqEntity, DelightfulMessageEntity $senderMessageEntity): DelightfulSeqEntity
    {
        $delightfulSeqStatus = DelightfulMessageStatus::Unread;
        # assistant可能参与private chat/group chatetc场景,read记忆o clock,needread自己conversation窗口down的message.
        $receiveSeqEntity = $this->delightfulChatDomainService->generateReceiveSequenceByChatMessage($senderSeqEntity, $senderMessageEntity, $delightfulSeqStatus);
        // 避免 seq 表承载too多feature,加too多索引,therefore将话题的message单独writeto topic_messages 表middle
        $this->delightfulChatDomainService->createTopicMessage($receiveSeqEntity);
        return $receiveSeqEntity;
    }

    /**
     * use大modelgeneratecontentsummary
     *
     * @param DelightfulUserAuthorization $authorization userauthorizationinfo
     * @param MessageHistory $messageHistory messagehistory
     * @param string $conversationId conversationID
     * @param string $topicId 话题ID，optional
     * @return string generate的summarytext
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
        # startrequest大model
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
        // iftitlelength超过20characterthenbacksurface的use...代替
        if (mb_strlen($choiceContent) > 20) {
            $choiceContent = mb_substr($choiceContent, 0, 20) . '...';
        }

        return $choiceContent;
    }

    private function getMessageTextContent(MessageInterface $message): string
    {
        // 暂o clock只handleuser的input，by及能get纯text的messagetype
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
        // 按照 $order indatabasemiddlequery，but是对return的result集降序rowcolumn了。
        $order = $conversationMessagesQueryDTO->getOrder();
        if ($order === Order::Desc) {
            // 对 $data 降序rowcolumn
            krsort($data);
        } else {
            // 对 $data 升序rowcolumn
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
        // type和contentgroup合in一起才是一可use的messagetype
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
     * according tocustomer端hair来的chatmessagetype,minutehairto对应的handle模piece.
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
            // message鉴权
            $this->checkSendMessageAuth($senderSeqDTO, $senderMessageDTO, $senderConversationEntity, $dataIsolation);
            // securityproperty保证，校验attachmentmiddle的filewhether属atcurrentuser
            $senderMessageDTO = $this->checkAndFillAttachments($senderMessageDTO, $dataIsolation);
            // 业务parameter校验
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
     * 校验attachmentmiddle的filewhether属atcurrentuser,并填充attachmentinfo.（file名/typeetcfield）.
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
     * ensure所have的conversationIDall属atcurrent账number，否thenthrowexception.
     *
     * @param DelightfulUserAuthorization $userAuthorization userauthorizationinfo
     * @param array $conversationIds 待check的conversationIDarray
     */
    private function checkConversationsOwnership(DelightfulUserAuthorization $userAuthorization, array $conversationIds): void
    {
        if (empty($conversationIds)) {
            return;
        }

        // 批quantitygetconversationinfo
        $conversations = $this->delightfulChatDomainService->getConversationsByIds($conversationIds);
        if (empty($conversations)) {
            return;
        }

        // 收集所haveconversationassociate的userID
        $userIds = [];
        foreach ($conversations as $conversation) {
            $userIds[] = $conversation->getUserId();
        }
        $userIds = array_unique($userIds);

        // 批quantitygetuserinfo
        $userEntities = $this->delightfulUserDomainService->getUserByIdsWithoutOrganization($userIds);
        $userMap = array_column($userEntities, 'delightful_id', 'user_id');

        // checkeachconversationwhether属atcurrentuser（passdelightful_id匹配）
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
     * 业务parameter校验
     * 对特定type的messageconduct业务rule校验.
     */
    private function validateBusinessParams(DelightfulMessageEntity $senderMessageDTO, DataIsolation $dataIsolation): void
    {
        $content = $senderMessageDTO->getContent();
        $messageType = $senderMessageDTO->getMessageType();

        // voicemessage校验
        if ($messageType === ChatMessageType::Voice && $content instanceof VoiceMessage) {
            $this->validateVoiceMessageParams($content, $dataIsolation);
        }
    }

    /**
     * 校验voicemessage的业务parameter.
     */
    private function validateVoiceMessageParams(VoiceMessage $voiceMessage, DataIsolation $dataIsolation): void
    {
        // 校验attachment
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

        // according toaudio的 file_id callfile领域getdetail，并填充attachment缺失的propertyvalue
        $this->fillVoiceAttachmentDetails($voiceMessage, $dataIsolation);

        // 重新get填充back的attachment
        $attachment = $voiceMessage->getAttachment();

        if ($attachment->getFileType() !== FileType::Audio) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, 'chat.message.voice.audio_format_required', ['type' => $attachment->getFileType()->name]);
        }

        // 校验录音duration
        $duration = $voiceMessage->getDuration();
        if ($duration !== null) {
            if ($duration <= 0) {
                ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, 'chat.message.voice.duration_positive', ['duration' => $duration]);
            }

            // defaultmost大60second
            $maxDuration = 60;
            if ($duration > $maxDuration) {
                ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, 'chat.message.voice.duration_exceeds_limit', ['max_duration' => $maxDuration, 'duration' => $duration]);
            }
        }
    }

    /**
     * according toaudio的 file_id callfile领域getdetail，并填充 VoiceMessage inherit的 ChatAttachment 缺失的propertyvalue.
     */
    private function fillVoiceAttachmentDetails(VoiceMessage $voiceMessage, DataIsolation $dataIsolation): void
    {
        $attachments = $voiceMessage->getAttachments();
        if (empty($attachments)) {
            return;
        }

        // callfile领域service填充attachmentdetail
        $filledAttachments = $this->delightfulChatFileDomainService->checkAndFillAttachments($attachments, $dataIsolation);

        // updatevoicemessage的attachmentinfo
        $voiceMessage->setAttachments($filledAttachments);
    }
}
