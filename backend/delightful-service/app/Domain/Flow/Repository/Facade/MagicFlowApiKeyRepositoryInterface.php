<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowApiKeyEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowApiKeyQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulFlowApiKeyRepositoryInterface
{
    public function getBySecretKey(FlowDataIsolation $dataIsolation, string $secretKey): ?DelightfulFlowApiKeyEntity;

    public function getByCode(FlowDataIsolation $dataIsolation, string $code, ?string $creator = null): ?DelightfulFlowApiKeyEntity;

    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowApiKeyEntity $magicFlowApiKeyEntity): DelightfulFlowApiKeyEntity;

    public function exist(FlowDataIsolation $dataIsolation, DelightfulFlowApiKeyEntity $magicFlowApiKeyEntity): bool;

    /**
     * @return array{total: int, list: array<DelightfulFlowApiKeyEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowApiKeyQuery $query, Page $page): array;

    public function destroy(FlowDataIsolation $dataIsolation, string $code): void;
}
