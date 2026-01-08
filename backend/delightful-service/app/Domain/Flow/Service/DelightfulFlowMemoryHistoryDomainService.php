<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowMemoryHistoryEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowMemoryHistoryQuery;
use App\Domain\Flow\Repository\Facade\DelightfulFlowMemoryHistoryRepositoryInterface;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowMemoryHistoryDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowMemoryHistoryRepositoryInterface $delightfulFlowMemoryHistoryRepository,
    ) {
    }

    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowMemoryHistoryEntity $delightfulFlowMemoryHistoryEntity): DelightfulFlowMemoryHistoryEntity
    {
        $delightfulFlowMemoryHistoryEntity->prepareForCreation();

        return $this->delightfulFlowMemoryHistoryRepository->create($dataIsolation, $delightfulFlowMemoryHistoryEntity);
    }

    /**
     * @return array{total: int, list: array<DelightfulFlowMemoryHistoryEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowMemoryHistoryQuery $query, Page $page): array
    {
        return $this->delightfulFlowMemoryHistoryRepository->queries($dataIsolation, $query, $page);
    }

    public function removeByConversationId(FlowDataIsolation $dataIsolation, string $conversationId): void
    {
        $this->delightfulFlowMemoryHistoryRepository->removeByConversationId($dataIsolation, $conversationId);
    }
}
