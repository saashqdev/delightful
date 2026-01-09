<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowVersionEntity;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFLowVersionQuery;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Qbhy\HyperfAuth\Authenticatable;

class DelightfulFlowVersionAppService extends AbstractFlowAppService
{
    /**
     * queryversion列table.
     * @return array{total: int, list: array<DelightfulFlowVersionEntity>, users: array}
     */
    public function queries(Authenticatable $authorization, DelightfulFLowVersionQuery $query, Page $page): array
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        if (empty($query->getFlowCode())) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.empty', ['label' => 'flow_code']);
        }

        $this->getFlowAndValidateOperation($dataIsolation, $query->getFlowCode(), 'read');

        $query->setSelect(['id', 'flow_code', 'code', 'name', 'description', 'organization_code', 'created_uid', 'created_at', 'updated_uid', 'updated_at', 'deleted_at']);
        $result = $this->delightfulFlowVersionDomainService->queries($dataIsolation, $query, $page);
        $userIds = [];
        foreach ($result['list'] as $item) {
            $userIds[] = $item->getCreator();
            $userIds[] = $item->getModifier();
        }
        $result['users'] = $this->delightfulUserDomainService->getByUserIds($this->createContactDataIsolation($dataIsolation), $userIds);
        return $result;
    }

    /**
     * getversion详情.
     */
    public function show(Authenticatable $authorization, string $flowCode, string $versionCode): DelightfulFlowVersionEntity
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        if (empty($flowCode)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.empty', ['label' => 'flow_code']);
        }
        $delightfulFlow = $this->getFlowAndValidateOperation($dataIsolation, $flowCode, 'read');
        $version = $this->delightfulFlowVersionDomainService->show($dataIsolation, $delightfulFlow->getCode(), $versionCode);
        $version->getDelightfulFlow()->setUserOperation($delightfulFlow->getUserOperation());
        return $version;
    }

    /**
     * 发布version.
     */
    public function publish(Authenticatable $authorization, DelightfulFlowVersionEntity $delightfulFlowVersionEntity): DelightfulFlowVersionEntity
    {
        if (empty($delightfulFlowVersionEntity->getFlowCode())) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.empty', ['label' => 'flow_code']);
        }
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        $delightfulFlow = $this->getFlowAndValidateOperation($dataIsolation, $delightfulFlowVersionEntity->getFlowCode(), 'edit');

        $version = $this->delightfulFlowVersionDomainService->publish($dataIsolation, $delightfulFlow, $delightfulFlowVersionEntity);
        $version->getDelightfulFlow()->setUserOperation($delightfulFlow->getUserOperation());
        return $version;
    }

    /**
     * 回滚version.
     */
    public function rollback(Authenticatable $authorization, string $flowCode, string $versionCode): DelightfulFlowVersionEntity
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        if (empty($flowCode)) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.empty', ['label' => 'flow_code']);
        }
        $delightfulFlow = $this->getFlowAndValidateOperation($dataIsolation, $flowCode, 'edit');

        $version = $this->delightfulFlowVersionDomainService->rollback($dataIsolation, $delightfulFlow, $versionCode);
        $version->getDelightfulFlow()->setUserOperation($delightfulFlow->getUserOperation());
        return $version;
    }
}
