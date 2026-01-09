<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Service;

use App\Domain\Chat\DTO\Agent\SenderExtraDTO;
use App\Domain\Chat\DTO\DelightfulMessageDTO;
use App\Domain\Chat\Entity\Items\SeqExtra;
use App\Domain\Chat\Entity\DelightfulConversationEntity;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\DelightfulTopicEntity;
use App\Domain\Chat\Event\Agent\UserCallAgentEvent;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Throwable;

/**
 * 临时message相关.
 */
class DelightfulIntermediateDomainService extends AbstractDomainService
{
    // 超级麦吉的交互指令临时message处理
    /**
     * @throws Throwable
     */
    public function handleBeDelightfulInstructionMessage(
        DelightfulMessageDTO $messageDTO,
        DataIsolation $dataIsolation,
        DelightfulConversationEntity $userConversationEntity,
    ): void {
        try {
            // 1. get发送者（当前user）info
            $senderUserId = $dataIsolation->getCurrentUserId();
            if (empty($senderUserId)) {
                ExceptionBuilder::throw(ChatErrorCode::USER_NOT_FOUND);
            }

            $senderUserEntity = $this->delightfulUserRepository->getUserById($senderUserId);
            if (! $senderUserEntity) {
                ExceptionBuilder::throw(ChatErrorCode::USER_NOT_FOUND);
            }
            $senderAccountEntity = $this->delightfulAccountRepository->getAccountInfoByDelightfulId($senderUserEntity->getDelightfulId());

            if (! $senderAccountEntity) {
                ExceptionBuilder::throw(ChatErrorCode::USER_NOT_FOUND);
            }

            // 2. get超级麦吉（接收者）info
            $agentUserId = $messageDTO->getReceiveId();
            $agentUserEntity = $this->delightfulUserRepository->getUserById($agentUserId);
            if (! $agentUserEntity) {
                ExceptionBuilder::throw(ChatErrorCode::USER_NOT_FOUND);
            }
            $agentAccountEntity = $this->delightfulAccountRepository->getAccountInfoByDelightfulId($agentUserEntity->getDelightfulId());

            if (! $agentAccountEntity) {
                ExceptionBuilder::throw(ChatErrorCode::AI_NOT_FOUND);
            }

            // 3. get agent 的 conversationId
            $agentConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId(
                $userConversationEntity->getId()
            );

            if ($agentConversationEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
            }

            $agentConversationId = $agentConversationEntity->getId();

            // 4. create序列实体 (临时message不需要持久化序列)
            $seqEntity = new DelightfulSeqEntity();
            $seqEntity->setAppMessageId($messageDTO->getAppMessageId());
            $seqEntity->setConversationId($agentConversationId);
            $seqEntity->setObjectId($agentAccountEntity->getDelightfulId());
            $seqEntity->setContent($messageDTO->getContent());

            // set额外info (include topicId)
            $seqExtra = new SeqExtra();
            // 从 messageDTO 中get topicId
            $topicId = $messageDTO->getTopicId() ?? '';

            // 如果 topicId 不为空，验证话题是否属于当前user
            if (empty($topicId)) {
                ExceptionBuilder::throw(ChatErrorCode::TOPIC_NOT_FOUND);
            }
            $this->validateTopicOwnership($topicId, $userConversationEntity->getId(), $dataIsolation);

            $seqExtra->setTopicId($topicId);
            $seqEntity->setExtra($seqExtra);

            // 5. createmessage实体 (转换DTO为Entity，但不持久化)
            $messageEntity = new DelightfulMessageEntity();
            $messageEntity->setSenderId($messageDTO->getSenderId());
            $messageEntity->setSenderType($messageDTO->getSenderType());
            $messageEntity->setSenderOrganizationCode($messageDTO->getSenderOrganizationCode());
            $messageEntity->setReceiveId($messageDTO->getReceiveId());
            $messageEntity->setReceiveType($messageDTO->getReceiveType());
            $messageEntity->setReceiveOrganizationCode($messageDTO->getReceiveOrganizationCode());
            $messageEntity->setAppMessageId($messageDTO->getAppMessageId());
            $messageEntity->setContent($messageDTO->getContent());
            $messageEntity->setMessageType($messageDTO->getMessageType());
            $messageEntity->setSendTime($messageDTO->getSendTime());

            // 6. create发送者额外info
            $senderExtraDTO = new SenderExtraDTO();
            // 临时message可能不需要环境ID，use默认value
            $senderExtraDTO->setDelightfulEnvId(null);

            // 7. 触发usercall超级麦吉event
            event_dispatch(new UserCallAgentEvent(
                $agentAccountEntity,
                $agentUserEntity,
                $senderAccountEntity,
                $senderUserEntity,
                $seqEntity,
                $messageEntity,
                $senderExtraDTO
            ));
        } catch (Throwable $e) {
            // recorderrorlog，但不阻断处理流程
            $this->logger?->error('HandleBeDelightfulInstructionMessage failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'messageDTO' => $messageDTO->toArray(),
            ]);
            throw $e;
        }
    }

    /**
     * 验证话题是否属于当前user.
     */
    private function validateTopicOwnership(string $topicId, string $conversationId, DataIsolation $dataIsolation): void
    {
        // create话题DTO
        $topicDTO = new DelightfulTopicEntity();
        $topicDTO->setTopicId($topicId);
        $topicDTO->setConversationId($conversationId);

        // get话题实体
        $topicEntity = $this->delightfulChatTopicRepository->getTopicEntity($topicDTO);
        if ($topicEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::TOPIC_NOT_FOUND);
        }

        // 验证话题所属的session是否属于当前user
        $this->checkAndGetSelfConversation($topicEntity->getConversationId(), $dataIsolation);
    }
}
