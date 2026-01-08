<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Facade;

use App\Domain\Flow\Entity\DelightfulFlowWaitMessageEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;

interface DelightfulFlowWaitMessageRepositoryInterface
{
    public function save(DelightfulFlowWaitMessageEntity $waitMessageEntity): DelightfulFlowWaitMessageEntity;

    public function find(FlowDataIsolation $dataIsolation, int $id): ?DelightfulFlowWaitMessageEntity;

    public function handled(FlowDataIsolation $dataIsolation, int $id): void;

    /**
     * @return DelightfulFlowWaitMessageEntity[]
     */
    public function listByUnhandledConversationId(FlowDataIsolation $dataIsolation, string $conversationId): array;
}
