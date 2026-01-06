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
        private readonly DelightfulFlowExecuteLogRepositoryInterface $delightfulFlowExecuteLogRepository,
    ) {
    }

    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $delightfulFlowExecuteLogEntity): DelightfulFlowExecuteLogEntity
    {
        $delightfulFlowExecuteLogEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        $delightfulFlowExecuteLogEntity->prepareForCreation();
        return $this->delightfulFlowExecuteLogRepository->create($dataIsolation, $delightfulFlowExecuteLogEntity);
    }

    public function updateStatus(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $delightfulFlowExecuteLogEntity): void
    {
        $this->delightfulFlowExecuteLogRepository->updateStatus($dataIsolation, $delightfulFlowExecuteLogEntity);
    }

    public function incrementRetryCount(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $delightfulFlowExecuteLogEntity): void
    {
        $this->delightfulFlowExecuteLogRepository->incrementRetryCount($dataIsolation, $delightfulFlowExecuteLogEntity);
    }

    /**
     * @return array<DelightfulFlowExecuteLogEntity>
     */
    public function getRunningTimeoutList(FlowDataIsolation $dataIsolation, int $timeout, Page $page): array
    {
        return $this->delightfulFlowExecuteLogRepository->getRunningTimeoutList($dataIsolation, $timeout, $page);
    }

    public function getByExecuteId(FlowDataIsolation $dataIsolation, string $executeId): ?DelightfulFlowExecuteLogEntity
    {
        return $this->delightfulFlowExecuteLogRepository->getByExecuteId($dataIsolation, $executeId);
    }
}
