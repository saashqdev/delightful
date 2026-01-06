<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowExecuteLogEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulFlowExecuteLogRepositoryInterface
{
    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $magicFlowExecuteLogEntity): DelightfulFlowExecuteLogEntity;

    public function updateStatus(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $magicFlowExecuteLogEntity): void;

    /**
     * @return array<DelightfulFlowExecuteLogEntity>
     */
    public function getRunningTimeoutList(FlowDataIsolation $dataIsolation, int $timeout, Page $page): array;

    public function getByExecuteId(FlowDataIsolation $dataIsolation, string $executeId): ?DelightfulFlowExecuteLogEntity;

    public function incrementRetryCount(FlowDataIsolation $dataIsolation, DelightfulFlowExecuteLogEntity $magicFlowExecuteLogEntity): void;
}
