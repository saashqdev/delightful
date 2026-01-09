<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowWaitMessageEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Repository\Facade\DelightfulFlowWaitMessageRepositoryInterface;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

class DelightfulFlowWaitMessageDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowWaitMessageRepositoryInterface $delightfulFlowWaitMessageRepository,
    ) {
    }

    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowWaitMessageEntity $savingWaitMessageEntity): DelightfulFlowWaitMessageEntity
    {
        $savingWaitMessageEntity->setCreator($dataIsolation->getCurrentUserId());
        $savingWaitMessageEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        if ($savingWaitMessageEntity->shouldCreate()) {
            $waitMessageEntity = clone $savingWaitMessageEntity;
            $waitMessageEntity->prepareForCreation();
        } else {
            // 暂o clockonlysupportcreate
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'unsupported update');
        }

        return $this->delightfulFlowWaitMessageRepository->save($waitMessageEntity);
    }

    public function handled(FlowDataIsolation $dataIsolation, int $id): void
    {
        $this->delightfulFlowWaitMessageRepository->handled($dataIsolation, $id);
    }

    public function getLastWaitMessage(FlowDataIsolation $dataIsolation, string $conversationId, string $flowCode, string $flowVersion): ?DelightfulFlowWaitMessageEntity
    {
        // shouldnotwillvery多,directlyfetch所have
        $waitMessages = $this->listByUnhandledConversationId($dataIsolation, $conversationId);
        foreach ($waitMessages as $waitMessage) {
            // iftimeout
            $isTimeout = false;
            if (! empty($waitMessage->getTimeout())) {
                $isTimeout = $waitMessage->getTimeout() < time();
            }
            // ifversion变more
            $isVersionChanged = $waitMessage->getFlowCode() !== $flowCode || $waitMessage->getFlowVersion() !== $flowVersion;
            if ($isTimeout || $isVersionChanged) {
                $this->handled($dataIsolation, $waitMessage->getId());
            } else {
                return $this->delightfulFlowWaitMessageRepository->find($dataIsolation, $waitMessage->getId());
            }
        }
        return null;
    }

    /**
     * @return DelightfulFlowWaitMessageEntity[]
     */
    public function listByUnhandledConversationId(FlowDataIsolation $dataIsolation, string $conversationId): array
    {
        return $this->delightfulFlowWaitMessageRepository->listByUnhandledConversationId($dataIsolation, $conversationId);
    }
}
