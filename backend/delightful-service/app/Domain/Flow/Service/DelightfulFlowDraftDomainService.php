<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowDraftEntity;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowDraftQuery;
use App\Domain\Flow\Repository\Facade\DelightfulFlowDraftRepositoryInterface;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Hyperf\DbConnection\Annotation\Transactional;

class DelightfulFlowDraftDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowDraftRepositoryInterface $delightfulFlowDraftRepository,
    ) {
    }

    /**
     * querydraftcolumntable.
     * @return array{total: int, list: array<DelightfulFlowDraftEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowDraftQuery $query, Page $page): array
    {
        return $this->delightfulFlowDraftRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * getdraftdetail.
     */
    public function show(FlowDataIsolation $dataIsolation, string $flowCode, string $draftCode): DelightfulFlowDraftEntity
    {
        $draft = $this->delightfulFlowDraftRepository->getByFlowCodeAndCode($dataIsolation, $flowCode, $draftCode);
        if (! $draft) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$draftCode} not存in");
        }
        return $draft;
    }

    /**
     * deletedraft.
     */
    public function remove(FlowDataIsolation $dataIsolation, string $flowCode, string $draftCode): void
    {
        $draft = $this->delightfulFlowDraftRepository->getByFlowCodeAndCode($dataIsolation, $flowCode, $draftCode);
        if (! $draft) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$draftCode} not存in");
        }
        $this->delightfulFlowDraftRepository->remove($dataIsolation, $draft);
    }

    /**
     * savedraft.
     */
    #[Transactional]
    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowDraftEntity $savingDelightfulFlowDraftEntity): DelightfulFlowDraftEntity
    {
        $savingDelightfulFlowDraftEntity->setCreator($dataIsolation->getCurrentUserId());
        $savingDelightfulFlowDraftEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        if ($savingDelightfulFlowDraftEntity->shouldCreate()) {
            $delightfulFlowDraftEntity = clone $savingDelightfulFlowDraftEntity;
            $delightfulFlowDraftEntity->prepareForCreation();
        } else {
            if (empty($savingDelightfulFlowDraftEntity->getCode())) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'code cannotfornull');
            }
            $delightfulFlowDraftEntity = $this->delightfulFlowDraftRepository->getByFlowCodeAndCode($dataIsolation, $savingDelightfulFlowDraftEntity->getFlowCode(), $savingDelightfulFlowDraftEntity->getCode());
            if (! $delightfulFlowDraftEntity) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$savingDelightfulFlowDraftEntity->getCode()} not存in");
            }
            $savingDelightfulFlowDraftEntity->prepareForModification($delightfulFlowDraftEntity);
        }

        $draft = $this->delightfulFlowDraftRepository->save($dataIsolation, $delightfulFlowDraftEntity);
        // onlyretainmostnewrecord
        $this->delightfulFlowDraftRepository->clearEarlyRecords($dataIsolation, $savingDelightfulFlowDraftEntity->getFlowCode());
        return $draft;
    }
}
