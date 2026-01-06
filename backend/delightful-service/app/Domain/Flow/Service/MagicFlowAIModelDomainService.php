<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowAIModelEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowAIModelQuery;
use App\Domain\Flow\Repository\Facade\DelightfulFlowAIModelRepositoryInterface;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowAIModelDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowAIModelRepositoryInterface $magicFlowAIModelRepository
    ) {
    }

    public function getByName(FlowDataIsolation $dataIsolation, string $name): ?DelightfulFlowAIModelEntity
    {
        return $this->magicFlowAIModelRepository->getByName($dataIsolation, $name);
    }

    /**
     * @return array{total: int, list: array<DelightfulFlowAIModelEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowAIModelQuery $query, Page $page): array
    {
        return $this->magicFlowAIModelRepository->queries($dataIsolation, $query, $page);
    }
}
