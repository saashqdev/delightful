<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowTriggerTestcaseEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowTriggerTestcaseQuery;
use App\Domain\Flow\Repository\Facade\DelightfulFlowTriggerTestcaseRepositoryInterface;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowTriggerTestcaseDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowTriggerTestcaseRepositoryInterface $magicFlowTriggerTestcaseRepository,
    ) {
    }

    /**
     * 保存测试集.
     */
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowTriggerTestcaseEntity $savingDelightfulFlowTriggerTestcaseEntity): DelightfulFlowTriggerTestcaseEntity
    {
        $savingDelightfulFlowTriggerTestcaseEntity->setCreator($dataIsolation->getCurrentUserId());
        $savingDelightfulFlowTriggerTestcaseEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        if ($savingDelightfulFlowTriggerTestcaseEntity->shouldCreate()) {
            $magicFlowTriggerTestcaseEntity = clone $savingDelightfulFlowTriggerTestcaseEntity;
            $magicFlowTriggerTestcaseEntity->prepareForCreation();
        } else {
            $magicFlowTriggerTestcaseEntity = $this->magicFlowTriggerTestcaseRepository->getByFlowCodeAndCode($dataIsolation, $savingDelightfulFlowTriggerTestcaseEntity->getFlowCode(), $savingDelightfulFlowTriggerTestcaseEntity->getCode());
            if (! $magicFlowTriggerTestcaseEntity) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$savingDelightfulFlowTriggerTestcaseEntity->getCode()} 不存在");
            }
            $savingDelightfulFlowTriggerTestcaseEntity->prepareForModification($magicFlowTriggerTestcaseEntity);
        }

        return $this->magicFlowTriggerTestcaseRepository->save($dataIsolation, $magicFlowTriggerTestcaseEntity);
    }

    /**
     * 查询测试集.
     * @return array{total: int, list: array<DelightfulFlowTriggerTestcaseEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowTriggerTestcaseQuery $query, Page $page): array
    {
        return $this->magicFlowTriggerTestcaseRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * 删除测试集.
     */
    public function remove(FlowDataIsolation $dataIsolation, DelightfulFlowTriggerTestcaseEntity $magicFlowTriggerTestcaseEntity): void
    {
        $this->magicFlowTriggerTestcaseRepository->remove($dataIsolation, $magicFlowTriggerTestcaseEntity);
    }

    /**
     * 获取测试集详情.
     */
    public function show(FlowDataIsolation $dataIsolation, string $flowCode, string $testcaseCode): DelightfulFlowTriggerTestcaseEntity
    {
        $testcase = $this->magicFlowTriggerTestcaseRepository->getByFlowCodeAndCode($dataIsolation, $flowCode, $testcaseCode);
        if (! $testcase) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$testcaseCode} 不存在");
        }
        return $testcase;
    }
}
