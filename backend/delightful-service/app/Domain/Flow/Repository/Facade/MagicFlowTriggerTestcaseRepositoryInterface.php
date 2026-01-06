<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowTriggerTestcaseEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowTriggerTestcaseQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulFlowTriggerTestcaseRepositoryInterface
{
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowTriggerTestcaseEntity $delightfulFlowTriggerTestcaseEntity): DelightfulFlowTriggerTestcaseEntity;

    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowTriggerTestcaseEntity;

    public function getByFlowCodeAndCode(FlowDataIsolation $dataIsolation, string $flowCode, string $code): ?DelightfulFlowTriggerTestcaseEntity;

    /**
     * @return array{total: int, list: array<DelightfulFlowTriggerTestcaseEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowTriggerTestcaseQuery $query, Page $page): array;

    public function remove(FlowDataIsolation $dataIsolation, DelightfulFlowTriggerTestcaseEntity $delightfulFlowTriggerTestcaseEntity): void;
}
