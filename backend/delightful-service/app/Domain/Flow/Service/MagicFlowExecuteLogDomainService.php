<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowExecuteLogEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Repository\Facade\DelightfulFlowExecuteLogRepositoryInterface;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowExecuteLogDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowExecuteLogRepositoryInterface $magicFlowExecuteLogRepository,
    ) {
    }

    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $magicFlowExecuteLogEntity): DelightfulFlowExecuteLogEntity
    {
        $magicFlowExecuteLogEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $magicFlowExecuteLogEntity->prepareForCreation();
        return $this->magicFlowExecuteLogRepository->create($dataIsolation, $magicFlowExecuteLogEntity);
    }

    public function updateStatus(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $magicFlowExecuteLogEntity): void
    {
        $this->magicFlowExecuteLogRepository->updateStatus($dataIsolation, $magicFlowExecuteLogEntity);
    }

    public function incrementRetryCount(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $magicFlowExecuteLogEntity): void
    {
        $this->magicFlowExecuteLogRepository->incrementRetryCount($dataIsolation, $magicFlowExecuteLogEntity);
    }

    /**
     * @return array<DelightfulFlowExecuteLogEntity>
     */
    public function getRunningTimeoutList(FlowDataIsolation $dataIsolation, int $timeout, Page $page): array
    {
        return $this->magicFlowExecuteLogRepository->getRunningTimeoutList($dataIsolation, $timeout, $page);
    }

    public function getByExecuteId(FlowDataIsolation $dataIsolation, string $executeId): ?DelightfulFlowExecuteLogEntity
    {
        return $this->magicFlowExecuteLogRepository->getByExecuteId($dataIsolation, $executeId);
    }
}
