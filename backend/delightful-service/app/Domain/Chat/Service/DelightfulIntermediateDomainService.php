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
 * temporarymessagerelatedclose.
 */
class DelightfulIntermediateDomainService extends AbstractDomainService
{
    // exceedslevelMagicinteractionfingercommandtemporarymessageprocess
    /**
     * @throws Throwable
     */
    public function handleBeDelightfulInstructionMessage(
        DelightfulMessageDTO $messageDTO,
        DataIsolation $dataIsolation,
        DelightfulConversationEntity $userConversationEntity,
    ): void {
        try {
            // 1. getsendperson(currentuser)info
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

            // 2. getexceedslevelMagic(receiveperson)info
            $agentUserId = $messageDTO->getReceiveId();
            $agentUserEntity = $this->delightfulUserRepository->getUserById($agentUserId);
            if (! $agentUserEntity) {
                ExceptionBuilder::throw(ChatErrorCode::USER_NOT_FOUND);
            }
            $agentAccountEntity = $this->delightfulAccountRepository->getAccountInfoByDelightfulId($agentUserEntity->getDelightfulId());

            if (! $agentAccountEntity) {
                ExceptionBuilder::throw(ChatErrorCode::AI_NOT_FOUND);
            }

            // 3. get agent  conversationId
            $agentConversationEntity = $this->delightfulConversationRepository->getReceiveConversationBySenderConversationId(
                $userConversationEntity->getId()
            );

            if ($agentConversationEntity === null) {
                ExceptionBuilder::throw(ChatErrorCode::CONVERSATION_NOT_FOUND);
            }

            $agentConversationId = $agentConversationEntity->getId();

            // 4. createsequencecolumnactualbody (temporarymessagenotneedpersistencesequencecolumn)
            $seqEntity = new DelightfulSeqEntity();
            $seqEntity->setAppMessageId($messageDTO->getAppMessageId());
            $seqEntity->setConversationId($agentConversationId);
            $seqEntity->setObjectId($agentAccountEntity->getDelightfulId());
            $seqEntity->setContent($messageDTO->getContent());

            // setquotaoutsideinfo (include topicId)
            $seqExtra = new SeqExtra();
            // from messageDTO middleget topicId
            $topicId = $messageDTO->getTopicId() ?? '';

            // if topicId notforempty,verifytopicwhetherbelongatcurrentuser
            if (empty($topicId)) {
                ExceptionBuilder::throw(ChatErrorCode::TOPIC_NOT_FOUND);
            }
            $this->validateTopicOwnership($topicId, $userConversationEntity->getId(), $dataIsolation);

            $seqExtra->setTopicId($topicId);
            $seqEntity->setExtra($seqExtra);

            // 5. createmessageactualbody (convertDTOforEntity,butnotpersistence)
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

            // 6. createsendquotaoutsideinfo
            $senderExtraDTO = new SenderExtraDTO();
            // temporarymessagemaybenotneedenvironmentID,usedefaultvalue
            $senderExtraDTO->setDelightfulEnvId(null);

            // 7. touchhairusercallexceedslevelMagicevent
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
            // recorderrorlog,butnotblockprocessprocess
            $this->logger?->error('HandleBeDelightfulInstructionMessage failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'messageDTO' => $messageDTO->toArray(),
            ]);
            throw $e;
        }
    }

    /**
     * verifytopicwhetherbelongatcurrentuser.
     */
    private function validateTopicOwnership(string $topicId, string $conversationId, DataIsolation $dataIsolation): void
    {
        // createtopicDTO
        $topicDTO = new DelightfulTopicEntity();
        $topicDTO->setTopicId($topicId);
        $topicDTO->setConversationId($conversationId);

        // gettopicactualbody
        $topicEntity = $this->delightfulChatTopicRepository->getTopicEntity($topicDTO);
        if ($topicEntity === null) {
            ExceptionBuilder::throw(ChatErrorCode::TOPIC_NOT_FOUND);
        }

        // verifytopicbelong tosessionwhetherbelongatcurrentuser
        $this->checkAndGetSelfConversation($topicEntity->getConversationId(), $dataIsolation);
    }
}
