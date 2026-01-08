<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowAIModelEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowAIModelQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulFlowAIModelRepositoryInterface
{
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowAIModelEntity $delightfulFlowAIModelEntity): DelightfulFlowAIModelEntity;

    public function getByName(FlowDataIsolation $dataIsolation, string $name): ?DelightfulFlowAIModelEntity;

    /**
     * @return array{total: int, list: array<DelightfulFlowAIModelEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowAIModelQuery $query, Page $page): array;
}
