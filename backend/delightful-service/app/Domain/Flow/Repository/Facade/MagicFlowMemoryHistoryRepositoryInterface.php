<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowMemoryHistoryEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowMemoryHistoryQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulFlowMemoryHistoryRepositoryInterface
{
    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowMemoryHistoryEntity $magicFlowMemoryHistoryEntity): DelightfulFlowMemoryHistoryEntity;

    /**
     * @return array{total: int, list: array<DelightfulFlowMemoryHistoryEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowMemoryHistoryQuery $query, Page $page): array;

    public function removeByConversationId(FlowDataIsolation $dataIsolation, string $conversationId): void;
}
