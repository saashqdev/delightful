<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\Chat\Service;

use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Chat\Service\DelightfulConversationDomainService;
use App\Domain\Chat\Service\DelightfulTopicDomainService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\Application\SuperAgent\Service\AbstractAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\AccountAppService;
use Delightful\BeDelightful\Domain\SuperAgent\Constant\AgentConstant;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

class ChatAppService extends AbstractAppService
{
    protected LoggerInterface $logger;

    public function __construct(
        protected DelightfulUserDomainService $userDomainService,
        protected AccountAppService $accountAppService,
        protected DelightfulConversationDomainService $delightfulConversationDomainService,
        protected DelightfulTopicDomainService $topicDomainService,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    /**
     * Initializes Delightful Chat conversation and topic.
     * This method sets up the necessary chat infrastructure for a "Be Delightful" interaction.
     * It fetches/creates an AI user, then gets/creates a conversation for the current user
     * with this AI user, and finally generates a topic ID for this conversation.
     *
     * @param DataIsolation $dataIsolation data isolation context
     * @return array an array containing the chat conversation ID and chat conversation topic ID
     * @throws Throwable if any error occurs during the process
     */
    public function initDelightfulChatConversation(DataIsolation $dataIsolation): array
    {
        $aiUserEntity = $this->getOrCreateBeDelightfulUser($dataIsolation);
        $currentUserId = $dataIsolation->getCurrentUserId();
        $aiUserId = $aiUserEntity->getUserId();
        $this->logger->info(sprintf('Getting or creating conversation for user ID: %s with AI user ID: %s in organization: %s', $currentUserId, $aiUserId, $dataIsolation->getCurrentOrganizationCode()));

        // Initialize conversation and topic for user
        $senderConversationEntity = $this->delightfulConversationDomainService->getOrCreateConversation(
            $currentUserId,
            $aiUserId,
            ConversationType::Ai
        );
        $this->logger->info(sprintf('Conversation obtained/created with ID: %s for user ID: %s, AI user ID: %s', $senderConversationEntity->getId(), $currentUserId, $aiUserId));

        // The number '3' here might refer to a specific type or flag for topic generation.
        // It's advisable to replace delightful numbers with named constants if possible.
        $topicId = $this->topicDomainService->agentSendMessageGetTopicId($senderConversationEntity, 3);
        $this->logger->info(sprintf('Topic ID obtained/created for conversation ID %s: %s', $senderConversationEntity->getId(), $topicId));

        return [$senderConversationEntity->getId(), $topicId];
    }

    /**
     * Get the Be Delightful agent user ID for the given organization.
     * This method retrieves the AI user entity for the Be Delightful agent
     * and returns its user ID.
     *
     * @param DataIsolation $dataIsolation data isolation context
     * @return string the agent user ID
     * @throws Throwable if the agent user is not found
     */
    public function getBeDelightfulAgentUserId(DataIsolation $dataIsolation): string
    {
        $aiUserEntity = $this->getOrCreateBeDelightfulUser($dataIsolation);
        return $aiUserEntity->getUserId();
    }

    /**
     * Get or create the Be Delightful AI user entity.
     * This is a private method to avoid code duplication between different public methods.
     *
     * @param DataIsolation $dataIsolation data isolation context
     * @return object the AI user entity
     * @throws Throwable if the agent user is not found after initialization attempts
     */
    private function getOrCreateBeDelightfulUser(DataIsolation $dataIsolation): object
    {
        $this->logger->info(sprintf('Attempting to get AI user with code: %s for organization: %s', AgentConstant::SUPER_DELIGHTFUL_CODE, $dataIsolation->getCurrentOrganizationCode()));
        $aiUserEntity = $this->userDomainService->getByAiCode($dataIsolation, AgentConstant::SUPER_DELIGHTFUL_CODE);

        if (empty($aiUserEntity)) {
            $this->logger->info(sprintf('AI user with code %s not found, attempting to initialize account for organization: %s', AgentConstant::SUPER_DELIGHTFUL_CODE, $dataIsolation->getCurrentOrganizationCode()));
            // Manually perform initialization if AI user is not found
            $this->accountAppService->initAccount($dataIsolation->getCurrentOrganizationCode());
            // Query again
            $aiUserEntity = $this->userDomainService->getByAiCode($dataIsolation, AgentConstant::SUPER_DELIGHTFUL_CODE);
            if (empty($aiUserEntity)) {
                $this->logger->error(sprintf('AI user with code %s still not found after attempting initialization for organization: %s', AgentConstant::SUPER_DELIGHTFUL_CODE, $dataIsolation->getCurrentOrganizationCode()));
                ExceptionBuilder::throw(GenericErrorCode::SystemError, 'workspace.be_delightful_user_not_found');
            }
            $this->logger->info(sprintf('AI user with code %s found after initialization for organization: %s', AgentConstant::SUPER_DELIGHTFUL_CODE, $dataIsolation->getCurrentOrganizationCode()));
        } else {
            $this->logger->info(sprintf('AI user with code %s found for organization: %s', AgentConstant::SUPER_DELIGHTFUL_CODE, $dataIsolation->getCurrentOrganizationCode()));
        }

        return $aiUserEntity;
    }
}
