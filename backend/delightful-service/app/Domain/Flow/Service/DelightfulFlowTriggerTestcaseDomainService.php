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
        private readonly DelightfulFlowTriggerTestcaseRepositoryInterface $delightfulFlowTriggerTestcaseRepository,
    ) {
    }

    /**
     * savetest集.
     */
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowTriggerTestcaseEntity $savingDelightfulFlowTriggerTestcaseEntity): DelightfulFlowTriggerTestcaseEntity
    {
        $savingDelightfulFlowTriggerTestcaseEntity->setCreator($dataIsolation->getCurrentUserId());
        $savingDelightfulFlowTriggerTestcaseEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        if ($savingDelightfulFlowTriggerTestcaseEntity->shouldCreate()) {
            $delightfulFlowTriggerTestcaseEntity = clone $savingDelightfulFlowTriggerTestcaseEntity;
            $delightfulFlowTriggerTestcaseEntity->prepareForCreation();
        } else {
            $delightfulFlowTriggerTestcaseEntity = $this->delightfulFlowTriggerTestcaseRepository->getByFlowCodeAndCode($dataIsolation, $savingDelightfulFlowTriggerTestcaseEntity->getFlowCode(), $savingDelightfulFlowTriggerTestcaseEntity->getCode());
            if (! $delightfulFlowTriggerTestcaseEntity) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$savingDelightfulFlowTriggerTestcaseEntity->getCode()} not存in");
            }
            $savingDelightfulFlowTriggerTestcaseEntity->prepareForModification($delightfulFlowTriggerTestcaseEntity);
        }

        return $this->delightfulFlowTriggerTestcaseRepository->save($dataIsolation, $delightfulFlowTriggerTestcaseEntity);
    }

    /**
     * querytest集.
     * @return array{total: int, list: array<DelightfulFlowTriggerTestcaseEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowTriggerTestcaseQuery $query, Page $page): array
    {
        return $this->delightfulFlowTriggerTestcaseRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * deletetest集.
     */
    public function remove(FlowDataIsolation $dataIsolation, DelightfulFlowTriggerTestcaseEntity $delightfulFlowTriggerTestcaseEntity): void
    {
        $this->delightfulFlowTriggerTestcaseRepository->remove($dataIsolation, $delightfulFlowTriggerTestcaseEntity);
    }

    /**
     * gettest集detail.
     */
    public function show(FlowDataIsolation $dataIsolation, string $flowCode, string $testcaseCode): DelightfulFlowTriggerTestcaseEntity
    {
        $testcase = $this->delightfulFlowTriggerTestcaseRepository->getByFlowCodeAndCode($dataIsolation, $flowCode, $testcaseCode);
        if (! $testcase) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$testcaseCode} not存in");
        }
        return $testcase;
    }
}
