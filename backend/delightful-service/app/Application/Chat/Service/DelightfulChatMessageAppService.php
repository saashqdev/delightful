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
        // 将所有 sid 都加入到room id value为 delightfulId 的room中
        $this->delightfulChatDomainService->joinRoom($userAuthorization->getDelightfulId(), $socket);
    }

    /**
     * return最大message的倒数 n 条序列.
     * @return ClientSequenceResponse[]
     * @deprecated
     */
    public function pullMessage(DelightfulUserAuthorization $userAuthorization, array $params): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        return $this->delightfulChatDomainService->pullMessage($dataIsolation, $params);
    }

    /**
     * return最大message的倒数 n 条序列.
     * @return ClientSequenceResponse[]
     */
    public function pullByPageToken(DelightfulUserAuthorization $userAuthorization, array $params): array
    {
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $pageSize = 200;
        return $this->delightfulChatDomainService->pullByPageToken($dataIsolation, $params, $pageSize);
    }

    /**
     * return最大message的倒数 n 条序列.
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
        // 是否是editmessage
        $editMessageOptions = $chatRequest->getData()->getEditMessageOptions();
        if ($editMessageOptions !== null) {
            $seqExtra->setEditMessageOptions($editMessageOptions);
        }
        // seq 的extensioninfo. 如果need检索话题的message,请query topic_messages 表
        $topicId && $seqExtra->setTopicId($topicId);
        $seqDTO->setExtra($seqExtra);
        // 如果是跟assistant的private chat，且没有话题 id，自动create一个话题
        if ($conversationEntity->getReceiveType() === ConversationType::Ai && empty($seqDTO->getExtra()?->getTopicId())) {
            $topicId = $this->delightfulTopicDomainService->agentSendMessageGetTopicId($conversationEntity, 0);
            // 不影响原有逻辑，将 topicId setting到 extra 中
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
        // checkconversation id所属organization，与current传入organizationencoding的一致性
        if ($conversationEntity->getUserOrganizationCode() !== $dataIsolation->getCurrentOrganizationCode()) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // 判断conversation的发起者是否是currentuser,并且不是assistant
        if ($conversationEntity->getReceiveType() !== ConversationType::Ai && $conversationEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
        }
        // conversation是否已被delete
        if ($conversationEntity->getStatus() === ConversationStatus::Delete) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_DELETED);
        }
        // 如果是editmessage，check被editmessage的legal性(自己发的message，且在currentconversation中)
        $this->checkEditMessageLegality($senderSeqDTO, $dataIsolation);
        return;
        // todo 如果message中有file:1.判断file的所有者是否是currentuser;2.判断user是否receive过这些file。
        /* @phpstan-ignore-next-line */
        $messageContent = $senderMessageDTO->getContent();
        if ($messageContent instanceof ChatFileInterface) {
            $fileIds = $messageContent->getFileIds();
            if (! empty($fileIds)) {
                // 批量queryfile所有权，而不是循环query
                $fileEntities = $this->delightfulChatFileDomainService->getFileEntitiesByFileIds($fileIds);

                // check是否所有file都存在
                $existingFileIds = array_map(static function (DelightfulChatFileEntity $fileEntity) {
                    return $fileEntity->getFileId();
                }, $fileEntities);

                // check是否有request的file ID 不在已query到的file ID 中
                $missingFileIds = array_diff($fileIds, $existingFileIds);
                if (! empty($missingFileIds)) {
                    ExceptionBuilder::throw(ChatErrorCode::FILE_NOT_FOUND);
                }

                // checkfile所有者是否是currentuser
                foreach ($fileEntities as $fileEntity) {
                    if ($fileEntity->getUserId() !== $dataIsolation->getCurrentUserId()) {
                        ExceptionBuilder::throw(ChatErrorCode::FILE_NOT_FOUND);
                    }
                }
            }
        }

        // todo check是否有发message的permission(need有好友关系，企业关系，集团关系，合作伙伴关系等)
    }

    /**
     * assistant给人类或者群发message,支持onlinemessage和offlinemessage(取决于user是否online).
     * @param DelightfulSeqEntity $aiSeqDTO 怎么传参can参考 api层的 aiSendMessage method
     * @param string $appMessageId message防重,客户端(includeflow)自己对messagegenerate一条encoding
     * @param bool $doNotParseReferMessageId 不由 chat 判断 referMessageId 的quote时机,由call方自己判断
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
            // 如果user给assistantsend了多条message,assistantreply时,need让user知晓assistantreply的是他的哪条message.
            $aiSeqDTO = $this->delightfulChatDomainService->aiReferMessage($aiSeqDTO, $doNotParseReferMessageId);
            // getassistant的conversation窗口
            $aiConversationEntity = $this->delightfulChatDomainService->getConversationById($aiSeqDTO->getConversationId());
            if ($aiConversationEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
            }
            // confirm发件人是否是assistant
            $aiUserId = $aiConversationEntity->getUserId();
            $aiUserEntity = $this->delightfulChatDomainService->getUserInfo($aiUserId);
            if ($aiUserEntity->getUserType() !== UserType::Ai) {
                ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
            }
            // 如果是assistant与人private chat，且assistantsend的message没有话题 id，则报错
            if ($aiConversationEntity->getReceiveType() === ConversationType::User && empty($aiSeqDTO->getExtra()?->getTopicId())) {
                ExceptionBuilder::throw(ChatErrorCode::TOPIC_ID_NOT_FOUND);
            }
            // assistant准备start发message了,endinputstatus
            $contentStruct = $aiSeqDTO->getContent();
            $isStream = $contentStruct instanceof StreamMessageInterface && $contentStruct->isStream();
            $beginStreamMessage = $isStream && $contentStruct instanceof StreamMessageInterface && $contentStruct->getStreamOptions()?->getStatus() === StreamMessageStatus::Start;
            if (! $isStream || $beginStreamMessage) {
                // 非streamresponse或者streamresponsestartinput
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
     * assistant给人类或者群发message,can不传conversation和话题 id,自动createconversation，非groupconversation自动适配话题 id.
     * @param string $appMessageId message防重,客户端(includeflow)自己对messagegenerate一条encoding
     * @param bool $doNotParseReferMessageId can不由 chat 判断 referMessageId 的quote时机,由call方自己判断
     * @throws Throwable
     */
    public function agentSendMessage(
        DelightfulSeqEntity $aiSeqDTO,
        string $senderUserId,
        string $receiverId,
        string $appMessageId = '',
        bool $doNotParseReferMessageId = false,// can不由 chat 判断 referMessageId 的quote时机,由call方自己判断
        ?Carbon $sendTime = null,
        ?ConversationType $receiverType = null
    ): array {
        // 1.判断 $senderUserId 与 $receiverUserId的conversation是否存在（参考getOrCreateConversationmethod）
        $senderConversationEntity = $this->delightfulConversationDomainService->getOrCreateConversation($senderUserId, $receiverId, $receiverType);
        // 还要createreceive方的conversation窗口，要不然无法create话题
        $this->delightfulConversationDomainService->getOrCreateConversation($receiverId, $senderUserId);

        // 2.如果 $seqExtra 不为 null，校验是否有 topic id，如果没有，参考 agentSendMessageGetTopicId method，得到话题 id
        $topicId = $aiSeqDTO->getExtra()?->getTopicId() ?? '';
        if (empty($topicId) && $receiverType !== ConversationType::Group) {
            $topicId = $this->delightfulTopicDomainService->agentSendMessageGetTopicId($senderConversationEntity, 0);
        }
        // 3.组装parameter，call aiSendMessage method
        $aiSeqDTO->getExtra() === null && $aiSeqDTO->setExtra(new SeqExtra());
        $aiSeqDTO->getExtra()->setTopicId($topicId);
        $aiSeqDTO->setConversationId($senderConversationEntity->getId());
        return $this->aiSendMessage($aiSeqDTO, $appMessageId, $sendTime, $doNotParseReferMessageId);
    }

    /**
     * 人类给assistant或者群发message,can不传conversation和话题 id,自动createconversation，非groupconversation自动适配话题 id.
     * @param string $appMessageId message防重,客户端(includeflow)自己对messagegenerate一条encoding
     * @param bool $doNotParseReferMessageId can不由 chat 判断 referMessageId 的quote时机,由call方自己判断
     * @throws Throwable
     */
    public function userSendMessageToAgent(
        DelightfulSeqEntity $aiSeqDTO,
        string $senderUserId,
        string $receiverId,
        string $appMessageId = '',
        bool $doNotParseReferMessageId = false,// can不由 chat 判断 referMessageId 的quote时机,由call方自己判断
        ?Carbon $sendTime = null,
        ?ConversationType $receiverType = null,
        string $topicId = ''
    ): array {
        // 1.判断 $senderUserId 与 $receiverUserId的conversation是否存在（参考getOrCreateConversationmethod）
        $senderConversationEntity = $this->delightfulConversationDomainService->getOrCreateConversation($senderUserId, $receiverId, $receiverType);
        // 如果receive方非group，则create senderUserId 与 receiverUserId 的conversation.
        if ($receiverType !== ConversationType::Group) {
            $this->delightfulConversationDomainService->getOrCreateConversation($receiverId, $senderUserId);
        }
        // 2.如果 $seqExtra 不为 null，校验是否有 topic id，如果没有，参考 agentSendMessageGetTopicId method，得到话题 id
        if (empty($topicId)) {
            $topicId = $aiSeqDTO->getExtra()?->getTopicId() ?? '';
        }

        if (empty($topicId) && $receiverType !== ConversationType::Group) {
            $topicId = $this->delightfulTopicDomainService->agentSendMessageGetTopicId($senderConversationEntity, 0);
        }

        // 如果是group，则不needget话题 id
        if ($receiverType === ConversationType::Group) {
            $topicId = '';
        }

        // 3.组装parameter，call sendMessageToAgent method
        $aiSeqDTO->getExtra() === null && $aiSeqDTO->setExtra(new SeqExtra());
        $aiSeqDTO->getExtra()->setTopicId($topicId);
        $aiSeqDTO->setConversationId($senderConversationEntity->getId());
        return $this->sendMessageToAgent($aiSeqDTO, $appMessageId, $sendTime, $doNotParseReferMessageId);
    }

    /**
     * assistant给人类或者群发message,支持onlinemessage和offlinemessage(取决于user是否online).
     * @param DelightfulSeqEntity $aiSeqDTO 怎么传参can参考 api层的 aiSendMessage method
     * @param string $appMessageId message防重,客户端(includeflow)自己对messagegenerate一条encoding
     * @param bool $doNotParseReferMessageId 不由 chat 判断 referMessageId 的quote时机,由call方自己判断
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
            // 如果user给assistantsend了多条message,assistantreply时,need让user知晓assistantreply的是他的哪条message.
            $aiSeqDTO = $this->delightfulChatDomainService->aiReferMessage($aiSeqDTO, $doNotParseReferMessageId);
            // getassistant的conversation窗口
            $aiConversationEntity = $this->delightfulChatDomainService->getConversationById($aiSeqDTO->getConversationId());
            if ($aiConversationEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
            }
            // confirm发件人是否是assistant
            $aiUserId = $aiConversationEntity->getUserId();
            $aiUserEntity = $this->delightfulChatDomainService->getUserInfo($aiUserId);
            // if ($aiUserEntity->getUserType() !== UserType::Ai) {
            //     ExceptionBuilder::throw(UserErrorCode::USER_NOT_EXIST);
            // }
            // 如果是assistant与人private chat，且assistantsend的message没有话题 id，则报错
            if ($aiConversationEntity->getReceiveType() === ConversationType::User && empty($aiSeqDTO->getExtra()?->getTopicId())) {
                ExceptionBuilder::throw(ChatErrorCode::TOPIC_ID_NOT_FOUND);
            }
            // assistant准备start发message了,endinputstatus
            $contentStruct = $aiSeqDTO->getContent();
            $isStream = $contentStruct instanceof StreamMessageInterface && $contentStruct->isStream();
            $beginStreamMessage = $isStream && $contentStruct instanceof StreamMessageInterface && $contentStruct->getStreamOptions()?->getStatus() === StreamMessageStatus::Start;
            if (! $isStream || $beginStreamMessage) {
                // 非streamresponse或者streamresponsestartinput
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
     * 分发asyncmessagequeue中的seq.
     * such asaccording to发件方的seq,为收件方generateseq,投递seq.
     * @throws Throwable
     */
    public function asyncHandlerChatMessage(DelightfulSeqEntity $senderSeqEntity): void
    {
        Db::beginTransaction();
        try {
            # 以下是chatmessage. 采取写扩散:如果是群,则为群member的每个人generateseq
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
                    // todo 群里面的话题message也write topic_messages 表中
                    // 将这些 seq_id merge为一条 mq message进行push/消费
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
     * conversation窗口滚动loadmessage.
     */
    public function getMessagesByConversationId(DelightfulUserAuthorization $userAuthorization, string $conversationId, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        // conversation所有权校验
        $this->checkConversationsOwnership($userAuthorization, [$conversationId]);

        // 按时间range，getconversation/话题的message
        $clientSeqList = $this->delightfulChatDomainService->getConversationChatMessages($conversationId, $conversationMessagesQueryDTO);
        return $this->formatConversationMessagesReturn($clientSeqList, $conversationMessagesQueryDTO);
    }

    /**
     * @deprecated
     */
    public function getMessageByConversationIds(DelightfulUserAuthorization $userAuthorization, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        // conversation所有权校验
        $conversationIds = $conversationMessagesQueryDTO->getConversationIds();
        if (! empty($conversationIds)) {
            $this->checkConversationsOwnership($userAuthorization, $conversationIds);
        }

        // getconversation的message（注意，feature目的与getMessagesByConversationIddifferent）
        $clientSeqList = $this->delightfulChatDomainService->getConversationsChatMessages($conversationMessagesQueryDTO);
        return $this->formatConversationMessagesReturn($clientSeqList, $conversationMessagesQueryDTO);
    }

    // 按conversation id groupget几条最新message
    public function getConversationsMessagesGroupById(DelightfulUserAuthorization $userAuthorization, MessagesQueryDTO $conversationMessagesQueryDTO): array
    {
        // conversation所有权校验
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
     * use大model对文本进行总结.
     */
    public function summarizeText(DelightfulUserAuthorization $authorization, string $textContent, string $language = 'zh_CN'): string
    {
        if (empty($textContent)) {
            return '';
        }
        $prompt = <<<'PROMPT'
        你是一个专业的contenttitlegenerate助手。请严格按照以下要求为conversationcontentgeneratetitle：

        ## task目标
        according toconversationcontent，generate一个简洁、准确的title，can概括conversation的核心theme。

        ## theme优先级原则
        当conversation涉及多个differenttheme时：
        1. 优先关注conversation中最后discussion的theme（最new话题）
        2. 以最近的conversationcontent为主要参考依据
        3. 如果最后的themediscussion较为充分，则以此作为title的核心
        4. ignore早期已经end的话题，除非它们与最新话题密切相关

        ## 严格要求
        1. titlelength：不超过 15 个字符。英文一个字母算一个字符，汉字一个字算一个字符，其他语种采用类似count方案。
        2. content相关：titlemust直接反映conversation的核心theme
        3. 语言style：use陈述性语句，避免疑问句
        4. outputformat：只outputtitlecontent，不要添加任何解释、标点或其他文字
        5. forbid行为：不要回答conversation中的问题，不要进行额外解释

        ## conversationcontent
        <CONVERSATION_START>
        {textContent}
        <CONVERSATION_END>

        ## output语言
        <LANGUAGE_START>
        请use{language}语言outputcontent
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
     * use大model对文本进行总结（usecustomizehint词）.
     *
     * @param DelightfulUserAuthorization $authorization userauthorization
     * @param string $customPrompt 完整的customizehint词（不做任何替换handle）
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
        // permission校验，判断user的message中，是否contain本次他想download的file
        $fileEntities = $this->delightfulChatFileDomainService->checkAndGetFilePaths($fileDTOs, $dataIsolation);
        // download时还原file原本的name
        $downloadNames = [];
        $fileDownloadUrls = [];
        $filePaths = [];
        foreach ($fileEntities as $fileEntity) {
            // filter掉有外链，但是没 file_key
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
     * 给发件方generatemessage和Seq.为了保证系统稳定性,给收件方generatemessage和Seq的步骤放在mqasync去做.
     * !!! 注意,transaction中投递 mq,可能transaction还没submit,mqmessage就已被消费.
     * @throws Throwable
     */
    public function delightfulChat(
        DelightfulSeqEntity $senderSeqDTO,
        DelightfulMessageEntity $senderMessageDTO,
        DelightfulConversationEntity $senderConversationEntity
    ): array {
        // 给发件方generatemessage和Seq
        // 从messageStruct中parse出来conversation窗口detail
        $receiveType = $senderConversationEntity->getReceiveType();
        if (! in_array($receiveType, [ConversationType::Ai, ConversationType::User, ConversationType::Group], true)) {
            ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_TYPE_ERROR);
        }

        $language = CoContext::getLanguage();
        // 审计需求：如果是editmessage，writemessageversion表，并update原message的version_id
        $extra = $senderSeqDTO->getExtra();
        // setting语言info
        $editMessageOptions = $extra?->getEditMessageOptions();
        if ($extra !== null && $editMessageOptions !== null && ! empty($editMessageOptions->getDelightfulMessageId())) {
            $senderMessageDTO->setDelightfulMessageId($editMessageOptions->getDelightfulMessageId());
            $messageVersionEntity = $this->delightfulChatDomainService->editMessage($senderMessageDTO);
            $editMessageOptions->setMessageVersionId($messageVersionEntity->getVersionId());
            $senderSeqDTO->setExtra($extra->setEditMessageOptions($editMessageOptions));
            // 再查一次 $messageEntity ，避免重复create
            $messageEntity = $this->delightfulChatDomainService->getMessageByDelightfulMessageId($senderMessageDTO->getDelightfulMessageId());
            $messageEntity && $messageEntity->setLanguage($language);
        }

        // 如果quote的message被edit过，那么修改 referMessageId 为original的message id
        $this->checkAndUpdateReferMessageId($senderSeqDTO);

        $senderMessageDTO->setLanguage($language);

        $messageStruct = $senderMessageDTO->getContent();
        if ($messageStruct instanceof StreamMessageInterface && $messageStruct->isStream()) {
            // streammessage的场景
            if ($messageStruct->getStreamOptions()->getStatus() === StreamMessageStatus::Start) {
                // 如果是start，call createAndSendStreamStartSequence method
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
            // 只在确定 $senderSeqEntity 和 $messageEntity，用于returndata结构
            $senderSeqEntity = $this->delightfulSeqDomainService->getSeqEntityByMessageId($senderMessageId);
            $messageEntity = $this->delightfulChatDomainService->getMessageByDelightfulMessageId($delightfulMessageId);
            // 将messagestreamreturn给current客户端! 但是还是willasyncpush给user的所有online客户端.
            return SeqAssembler::getClientSeqStruct($senderSeqEntity, $messageEntity)->toArray();
        }

        # 非streammessage
        try {
            Db::beginTransaction();
            if (! isset($messageEntity)) {
                $messageEntity = $this->delightfulChatDomainService->createDelightfulMessageByAppClient($senderMessageDTO, $senderConversationEntity);
            }
            // 给自己的messagestreamgenerate序列,并确定message的receive人列表
            $senderSeqEntity = $this->delightfulChatDomainService->generateSenderSequenceByChatMessage($senderSeqDTO, $messageEntity, $senderConversationEntity);
            // 避免 seq 表承载太多feature,加太多索引,因此将话题的message单独write到 topic_messages 表中
            $this->delightfulChatDomainService->createTopicMessage($senderSeqEntity);
            // 确定message优先级
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
                // 为了保证收发双方的message顺序一致性，如果是private chat，则syncgenerate seq
                $receiveSeqEntity = $this->syncHandlerSingleChatMessage($senderSeqEntity, $messageEntity);
            } elseif ($conversationType === ConversationType::Group) {
                // group chat等场景async给收件方generateSeq并push给收件方
                $this->delightfulChatDomainService->dispatchSeq($senderChatSeqCreatedEvent);
            } else {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_TYPE_ERROR);
            }
            Db::commit();
        } catch (Throwable $exception) {
            Db::rollBack();
            throw $exception;
        }
        // use mq pushmessage给收件方
        isset($receiveSeqEntity) && $this->pushReceiveChatSequence($messageEntity, $receiveSeqEntity);
        // asyncpushmessage给自己的其他设备
        if ($messageEntity->getSenderType() !== ConversationType::Ai) {
            co(function () use ($senderChatSeqCreatedEvent) {
                $this->delightfulChatDomainService->pushChatSequence($senderChatSeqCreatedEvent);
            });
        }

        // 如果是editmessage，且是useredit了assistant发来的approvalform时，returnnullarray。
        // 因为此时create的 seq_id 是assistant的，不是user的，returnwill造成困扰。
        // 经由 mq 分发message后，userwillasync收到属于他自己的messagepush。
        if (isset($editMessageOptions) && ! empty($editMessageOptions->getDelightfulMessageId())
            && $messageEntity->getSenderId() !== $senderMessageDTO->getSenderId()) {
            return [];
        }

        // 将messagestreamreturn给current客户端! 但是还是willasyncpush给user的所有online客户端.
        return SeqAssembler::getClientSeqStruct($senderSeqEntity, $messageEntity)->toArray();
    }

    /**
     * 如果quote的message被edit过，那么修改 referMessageId 为original的message id.
     */
    public function checkAndUpdateReferMessageId(DelightfulSeqEntity $senderSeqDTO): void
    {
        // getquotemessage的ID
        $referMessageId = $senderSeqDTO->getReferMessageId();
        if (empty($referMessageId)) {
            return;
        }

        // query被quote的message
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
        // 便于前端渲染，updatequotemessageID为originalmessageID
        $senderSeqDTO->setReferMessageId($delightfulSeqEntity->getMessageId());
    }

    /**
     * 开发阶段,前端对接有时间差,上下文compatible性handle.
     */
    public function setUserContext(string $userToken, ?DelightfulContext $delightfulContext): void
    {
        if (! $delightfulContext) {
            ExceptionBuilder::throw(ChatErrorCode::CONTEXT_LOST);
        }
        // 为了支持一个ws链receive发多个账号的message,allow在message上下文中传入账号 token
        if (! $delightfulContext->getAuthorization()) {
            $delightfulContext->setAuthorization($userToken);
        }
        // 协程上下文中settinguserinfo,供 WebsocketChatUserGuard use
        WebSocketContext::set(DelightfulContext::class, $delightfulContext);
    }

    /**
     * chat窗口打字时补全userinput。为了适配group chat，这里的 role 其实是user的nickname，而不是roletype。
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
        // get话题的最近 20 条conversationrecord
        $clientSeqResponseDTOS = $this->delightfulChatDomainService->getConversationChatMessages($conversationId, $conversationMessagesQueryDTO);
        // get收发双方的userinfo，用于补全时增强roletype
        $userIds = [];
        foreach ($clientSeqResponseDTOS as $clientSeqResponseDTO) {
            // 收集 user_id
            $userIds[] = $clientSeqResponseDTO->getSeq()->getMessage()->getSenderId();
        }
        // 把自己的 user_id 也加进去
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
            // 暂时只handleuser的input，以及能get纯文本的messagetype
            $messageContent = $this->getMessageTextContent($message);
            if (empty($messageContent)) {
                continue;
            }

            // according toparameter决定usenickname还是传统的 role
            if ($useNicknameAsRole) {
                $userMessages[$clientSeqResponseDTO->getSeq()->getSeqId()] = [
                    'role' => $delightfulUserEntity->getNickname(),
                    'role_description' => $delightfulUserEntity->getDescription(),
                    'content' => $messageContent,
                ];
            } else {
                // use传统的 role，判断是否为 AI user
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
        // according to seq_id 升序排列
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
     * 为了保证收发双方的message顺序一致性，如果是private chat，则syncgenerate seq.
     * @throws Throwable
     */
    private function syncHandlerSingleChatMessage(DelightfulSeqEntity $senderSeqEntity, DelightfulMessageEntity $senderMessageEntity): DelightfulSeqEntity
    {
        $delightfulSeqStatus = DelightfulMessageStatus::Unread;
        # assistant可能参与private chat/group chat等场景,read记忆时,needread自己conversation窗口下的message.
        $receiveSeqEntity = $this->delightfulChatDomainService->generateReceiveSequenceByChatMessage($senderSeqEntity, $senderMessageEntity, $delightfulSeqStatus);
        // 避免 seq 表承载太多feature,加太多索引,因此将话题的message单独write到 topic_messages 表中
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
     * @return string generate的summary文本
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
        // 如果titlelength超过20个字符则后面的用...代替
        if (mb_strlen($choiceContent) > 20) {
            $choiceContent = mb_substr($choiceContent, 0, 20) . '...';
        }

        return $choiceContent;
    }

    private function getMessageTextContent(MessageInterface $message): string
    {
        // 暂时只handleuser的input，以及能get纯文本的messagetype
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
        // 按照 $order 在database中query，但是对return的result集降序排列了。
        $order = $conversationMessagesQueryDTO->getOrder();
        if ($order === Order::Desc) {
            // 对 $data 降序排列
            krsort($data);
        } else {
            // 对 $data 升序排列
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
        // type和content组合在一起才是一个可用的messagetype
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
     * according to客户端发来的chatmessagetype,分发到对应的handle模块.
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
            // 安全性保证，校验attachment中的file是否属于currentuser
            $senderMessageDTO = $this->checkAndFillAttachments($senderMessageDTO, $dataIsolation);
            // 业务parameter校验
            $this->validateBusinessParams($senderMessageDTO, $dataIsolation);
            // message分发
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
     * 校验attachment中的file是否属于currentuser,并填充attachmentinfo.（file名/type等field）.
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
     * checkconversation所有权
     * ensure所有的conversationID都属于current账号，否则throwexception.
     *
     * @param DelightfulUserAuthorization $userAuthorization userauthorizationinfo
     * @param array $conversationIds 待check的conversationIDarray
     */
    private function checkConversationsOwnership(DelightfulUserAuthorization $userAuthorization, array $conversationIds): void
    {
        if (empty($conversationIds)) {
            return;
        }

        // 批量getconversationinfo
        $conversations = $this->delightfulChatDomainService->getConversationsByIds($conversationIds);
        if (empty($conversations)) {
            return;
        }

        // 收集所有conversationassociate的userID
        $userIds = [];
        foreach ($conversations as $conversation) {
            $userIds[] = $conversation->getUserId();
        }
        $userIds = array_unique($userIds);

        // 批量getuserinfo
        $userEntities = $this->delightfulUserDomainService->getUserByIdsWithoutOrganization($userIds);
        $userMap = array_column($userEntities, 'delightful_id', 'user_id');

        // check每个conversation是否属于currentuser（passdelightful_id匹配）
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
     * 对特定type的message进行业务规则校验.
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

        // 重新get填充后的attachment
        $attachment = $voiceMessage->getAttachment();

        if ($attachment->getFileType() !== FileType::Audio) {
            ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, 'chat.message.voice.audio_format_required', ['type' => $attachment->getFileType()->name]);
        }

        // 校验录音时长
        $duration = $voiceMessage->getDuration();
        if ($duration !== null) {
            if ($duration <= 0) {
                ExceptionBuilder::throw(ChatErrorCode::MESSAGE_TYPE_ERROR, 'chat.message.voice.duration_positive', ['duration' => $duration]);
            }

            // default最大60秒
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

        // callfile领域服务填充attachmentdetail
        $filledAttachments = $this->delightfulChatFileDomainService->checkAndFillAttachments($attachments, $dataIsolation);

        // updatevoicemessage的attachmentinfo
        $voiceMessage->setAttachments($filledAttachments);
    }
}
