<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowMultiModalLogEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Repository\Facade\DelightfulFlowMultiModalLogRepositoryInterface;

class DelightfulFlowMultiModalLogDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowMultiModalLogRepositoryInterface $delightfulFlowMultiModalLogRepository,
    ) {
    }

    /**
     * 创建多模态日志记录.
     */
    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowMultiModalLogEntity $entity): DelightfulFlowMultiModalLogEntity
    {
        $entity->prepareForCreation();
        return $this->delightfulFlowMultiModalLogRepository->create($dataIsolation, $entity);
    }

    /**
     * 根据ID获取多模态日志记录.
     */
    public function getById(FlowDataIsolation $dataIsolation, int $id): ?DelightfulFlowMultiModalLogEntity
    {
        return $this->delightfulFlowMultiModalLogRepository->getById($dataIsolation, $id);
    }

    /**
     * 根据消息ID获取多模态日志记录.
     */
    public function getByMessageId(FlowDataIsolation $dataIsolation, string $messageId): ?DelightfulFlowMultiModalLogEntity
    {
        return $this->delightfulFlowMultiModalLogRepository->getByMessageId($dataIsolation, $messageId);
    }

    /**
     * 批量获取多个消息ID对应的多模态日志记录.
     *
     * @param array<string> $messageIds
     * @return array<DelightfulFlowMultiModalLogEntity>
     */
    public function getByMessageIds(FlowDataIsolation $dataIsolation, array $messageIds, bool $keyByMessageId = false): array
    {
        return $this->delightfulFlowMultiModalLogRepository->getByMessageIds($dataIsolation, $messageIds, $keyByMessageId);
    }
}
