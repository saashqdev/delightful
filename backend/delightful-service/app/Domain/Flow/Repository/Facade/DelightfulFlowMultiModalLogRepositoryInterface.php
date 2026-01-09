<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowMultiModalLogEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;

interface DelightfulFlowMultiModalLogRepositoryInterface
{
    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowMultiModalLogEntity $entity): DelightfulFlowMultiModalLogEntity;

    public function getById(FlowDataIsolation $dataIsolation, int $id): ?DelightfulFlowMultiModalLogEntity;

    public function getByMessageId(FlowDataIsolation $dataIsolation, string $messageId): ?DelightfulFlowMultiModalLogEntity;

    /**
     * 批quantityget多messageIDto应多模statelogrecord.
     *
     * @param array<string> $messageIds
     * @return array<DelightfulFlowMultiModalLogEntity>
     */
    public function getByMessageIds(FlowDataIsolation $dataIsolation, array $messageIds, bool $keyByMessageId = false): array;
}
