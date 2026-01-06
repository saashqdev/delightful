<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowQuery;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\Infrastructure\Core\ValueObject\Page;

interface DelightfulFlowRepositoryInterface
{
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $magicFlowEntity): DelightfulFlowEntity;

    public function getByCode(FlowDataIsolation $dataIsolation, string $code): ?DelightfulFlowEntity;

    /**
     * @return array<DelightfulFlowEntity>
     */
    public function getByCodes(FlowDataIsolation $dataIsolation, array $codes): array;

    public function getByName(FlowDataIsolation $dataIsolation, string $name, Type $type): ?DelightfulFlowEntity;

    public function remove(FlowDataIsolation $dataIsolation, DelightfulFlowEntity $magicFlowEntity): void;

    /**
     * @return array{total: int, list: array<DelightfulFlowEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowQuery $query, Page $page): array;

    public function changeEnable(FlowDataIsolation $dataIsolation, string $code, bool $enable): void;

    public function getToolsInfo(FlowDataIsolation $dataIsolation, string $toolSetId): array;
}
