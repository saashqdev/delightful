<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Repository\Facade;

use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeBaseDataIsolation;
use App\Domain\KnowledgeBase\Entity\ValueObject\Query\KnowledgeBaseQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface KnowledgeBaseRepositoryInterface
{
    public function getByCode(KnowledgeBaseDataIsolation $dataIsolation, string $code): ?KnowledgeBaseEntity;

    /**
     * @return array<KnowledgeBaseEntity>
     */
    public function getByCodes(KnowledgeBaseDataIsolation $dataIsolation, array $codes): array;

    public function save(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $delightfulFlowKnowledgeEntity): KnowledgeBaseEntity;

    /**
     * @return array{total: int, list: array<KnowledgeBaseEntity>}
     */
    public function queries(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseQuery $query, Page $page): array;

    public function destroy(KnowledgeBaseDataIsolation $dataIsolation, KnowledgeBaseEntity $delightfulFlowKnowledgeEntity): void;

    public function changeSyncStatus(KnowledgeBaseEntity $entity): void;

    public function exist(KnowledgeBaseDataIsolation $dataIsolation, string $code): bool;

    public function updateWordCount(KnowledgeBaseDataIsolation $dataIsolation, string $knowledgeCode, int $deltaWordCount): void;
}
