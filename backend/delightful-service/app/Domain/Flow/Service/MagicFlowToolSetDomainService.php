<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowToolSetEntity;
use App\Domain\Flow\Entity\ValueObject\ConstValue;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowToolSetQuery;
use App\Domain\Flow\Event\DelightfulFLowToolSetSavedEvent;
use App\Domain\Flow\Repository\Facade\DelightfulFlowToolSetRepositoryInterface;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Delightful\AsyncEvent\AsyncEventUtil;

class DelightfulFlowToolSetDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowToolSetRepositoryInterface $magicFlowToolSetRepository,
    ) {
    }

    public function getByCode(FlowDataIsolation $dataIsolation, string $code): DelightfulFlowToolSetEntity
    {
        if ($code === ConstValue::TOOL_SET_DEFAULT_CODE) {
            return DelightfulFlowToolSetEntity::createNotGrouped($dataIsolation->getCurrentOrganizationCode());
        }
        $toolSet = $this->magicFlowToolSetRepository->getByCode($dataIsolation, $code);
        if (! $toolSet) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.not_found', ['label' => $code]);
        }
        return $toolSet;
    }

    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowToolSetEntity $savingDelightfulFLowToolSetEntity): DelightfulFlowToolSetEntity
    {
        $savingDelightfulFLowToolSetEntity->setCreator($dataIsolation->getCurrentUserId());
        $savingDelightfulFLowToolSetEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        if ($savingDelightfulFLowToolSetEntity->shouldCreate()) {
            $magicFlowToolSetEntity = clone $savingDelightfulFLowToolSetEntity;
            $magicFlowToolSetEntity->prepareForCreation();
        } else {
            if ($savingDelightfulFLowToolSetEntity->getCode() === ConstValue::TOOL_SET_DEFAULT_CODE) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.tool_set.not_edit_default_tool_set');
            }
            $magicFlowToolSetEntity = $this->magicFlowToolSetRepository->getByCode($dataIsolation, $savingDelightfulFLowToolSetEntity->getCode());
            if (! $magicFlowToolSetEntity) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.not_found', ['label' => $savingDelightfulFLowToolSetEntity->getCode()]);
            }
            $savingDelightfulFLowToolSetEntity->prepareForModification($magicFlowToolSetEntity);
        }
        $toolSet = $this->magicFlowToolSetRepository->save($dataIsolation, $magicFlowToolSetEntity);
        AsyncEventUtil::dispatch(new DelightfulFLowToolSetSavedEvent($toolSet, $savingDelightfulFLowToolSetEntity->shouldCreate()));
        return $toolSet;
    }

    public function create(FlowDataIsolation $dataIsolation, DelightfulFlowToolSetEntity $savingDelightfulFLowToolSetEntity): DelightfulFlowToolSetEntity
    {
        $toolSet = $this->magicFlowToolSetRepository->save($dataIsolation, $savingDelightfulFLowToolSetEntity);
        $savedEvent = new DelightfulFLowToolSetSavedEvent($toolSet, true);
        AsyncEventUtil::dispatch($savedEvent);
        return $toolSet;
    }

    /**
     * @return array{total: int, list: array<DelightfulFlowToolSetEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowToolSetQuery $query, Page $page): array
    {
        return $this->magicFlowToolSetRepository->queries($dataIsolation, $query, $page);
    }

    public function destroy(FlowDataIsolation $dataIsolation, string $code): void
    {
        $toolSet = $this->magicFlowToolSetRepository->getByCode($dataIsolation, $code);
        if (! $toolSet) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'flow.common.not_found', ['label' => $code]);
        }
        $this->magicFlowToolSetRepository->destroy($dataIsolation, $code);
    }
}
