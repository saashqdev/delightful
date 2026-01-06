<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowDraftEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowDraftQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulFlowDraftRepositoryInterface
{
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowDraftEntity $delightfulFlowDraftEntity): DelightfulFlowDraftEntity;

    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowDraftEntity;

    public function getByFlowCodeAndCode(FlowDataIsolation $dataIsolation, string $flowCode, string $code): ?DelightfulFlowDraftEntity;

    /**
     * @return array{total: int, list: array<DelightfulFlowDraftEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowDraftQuery $query, Page $page): array;

    public function remove(FlowDataIsolation $dataIsolation, DelightfulFlowDraftEntity $delightfulFlowDraftEntity): void;

    public function clearEarlyRecords(FlowDataIsolation $dataIsolation, string $flowCode): void;
}
