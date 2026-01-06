<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowToolSetEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowToolSetQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulFlowToolSetRepositoryInterface
{
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowToolSetEntity $magicFlowToolSetEntity): DelightfulFlowToolSetEntity;

    public function destroy(FlowDataIsolation $dataIsolation, string $code): void;

    /**
     * @return array{total: int, list: array<DelightfulFlowToolSetEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowToolSetQuery $query, Page $page): array;

    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowToolSetEntity;
}
