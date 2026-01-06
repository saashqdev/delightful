<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowVersionEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowVersionQuery;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulFlowVersionRepositoryInterface
{
    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowVersionEntity $magicFlowVersionEntity): DelightfulFlowVersionEntity;

    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowVersionEntity;

    public function getByFlowCodeAndCode(FlowDataIsolation $dataIsolation, string $flowCode, string $code): ?DelightfulFlowVersionEntity;

    /**
     * @return array{total: int, list: array<DelightfulFlowVersionEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowVersionQuery $query, Page $page): array;

    public function getLastVersion(FlowDataIsolation $dataIsolation, string $flowCode): ?DelightfulFlowVersionEntity;

    public function existVersion(FlowDataIsolation $dataIsolation, string $flowCode): bool;

    /**
     * @param array<string> $versionCodes
     * @return array<DelightfulFlowVersionEntity>
     */
    public function getByCodes(FlowDataIsolation $dataIsolation, array $versionCodes): array;
}
