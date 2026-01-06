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
     * 查询草稿列表.
     * @return array{total: int, list: array<DelightfulFlowDraftEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFLowDraftQuery $query, Page $page): array
    {
        return $this->delightfulFlowDraftRepository->queries($dataIsolation, $query, $page);
    }

    /**
     * 获取草稿详情.
     */
    public function show(FlowDataIsolation $dataIsolation, string $flowCode, string $draftCode): DelightfulFlowDraftEntity
    {
        $draft = $this->delightfulFlowDraftRepository->getByFlowCodeAndCode($dataIsolation, $flowCode, $draftCode);
        if (! $draft) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$draftCode} 不存在");
        }
        return $draft;
    }

    /**
     * 删除草稿.
     */
    public function remove(FlowDataIsolation $dataIsolation, string $flowCode, string $draftCode): void
    {
        $draft = $this->delightfulFlowDraftRepository->getByFlowCodeAndCode($dataIsolation, $flowCode, $draftCode);
        if (! $draft) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$draftCode} 不存在");
        }
        $this->delightfulFlowDraftRepository->remove($dataIsolation, $draft);
    }

    /**
     * 保存草稿.
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
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'code 不能为空');
            }
            $delightfulFlowDraftEntity = $this->delightfulFlowDraftRepository->getByFlowCodeAndCode($dataIsolation, $savingDelightfulFlowDraftEntity->getFlowCode(), $savingDelightfulFlowDraftEntity->getCode());
            if (! $delightfulFlowDraftEntity) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, "{$savingDelightfulFlowDraftEntity->getCode()} 不存在");
            }
            $savingDelightfulFlowDraftEntity->prepareForModification($delightfulFlowDraftEntity);
        }

        $draft = $this->delightfulFlowDraftRepository->save($dataIsolation, $delightfulFlowDraftEntity);
        // 仅保留最新的记录
        $this->delightfulFlowDraftRepository->clearEarlyRecords($dataIsolation, $savingDelightfulFlowDraftEntity->getFlowCode());
        return $draft;
    }
}
