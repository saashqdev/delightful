<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowMultiModalLogEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Repository\Facade\DelightfulFlowMultiModalLogRepositoryInterface;

class DelightfulFlowMultiModalLogDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowMultiModalLogRepositoryInterface $delightfulFlowMultiModalLogRepository,
    ) {
    }

    /**
     * create多模statelogrecord.
     */
    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowMultiModalLogEntity $entity): DelightfulFlowMultiModalLogEntity
    {
        $entity->prepareForCreation();
        return $this->delightfulFlowMultiModalLogRepository->create($dataIsolation, $entity);
    }

    /**
     * according toIDget多模statelogrecord.
     */
    public function getById(FlowDataIsolation $dataIsolation, int $id): ?DelightfulFlowMultiModalLogEntity
    {
        return $this->delightfulFlowMultiModalLogRepository->getById($dataIsolation, $id);
    }

    /**
     * according tomessageIDget多模statelogrecord.
     */
    public function getByMessageId(FlowDataIsolation $dataIsolation, string $messageId): ?DelightfulFlowMultiModalLogEntity
    {
        return $this->delightfulFlowMultiModalLogRepository->getByMessageId($dataIsolation, $messageId);
    }

    /**
     * batchquantityget多messageIDto应多模statelogrecord.
     *
     * @param array<string> $messageIds
     * @return array<DelightfulFlowMultiModalLogEntity>
     */
    public function getByMessageIds(FlowDataIsolation $dataIsolation, array $messageIds, bool $keyByMessageId = false): array
    {
        return $this->delightfulFlowMultiModalLogRepository->getByMessageIds($dataIsolation, $messageIds, $keyByMessageId);
    }
}
