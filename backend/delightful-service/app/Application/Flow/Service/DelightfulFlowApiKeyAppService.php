<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowApiKeyEntity;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowApiKeyQuery;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use Qbhy\HyperfAuth\Authenticatable;

class DelightfulFlowApiKeyAppService extends AbstractFlowAppService
{
    public function save(Authenticatable $authorization, DelightfulFlowApiKeyEntity $savingDelightfulFlowApiKeyEntity): DelightfulFlowApiKeyEntity
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);

        $delightfulFlow = $this->delightfulFlowDomainService->getByCode($dataIsolation, $savingDelightfulFlowApiKeyEntity->getFlowCode());
        if (! $delightfulFlow) {
            ExceptionBuilder::throw(FlowErrorCode::BusinessException, 'flow.common.not_found', ['label' => $savingDelightfulFlowApiKeyEntity->getFlowCode()]);
        }
        // 需要至少能查看，才能维护自己的 API-KEY
        $this->getFlowOperation($dataIsolation, $delightfulFlow)->validate('r', $savingDelightfulFlowApiKeyEntity->getFlowCode());
        return $this->delightfulFlowApiKeyDomainService->save($dataIsolation, $savingDelightfulFlowApiKeyEntity);
    }

    public function changeSecretKey(Authenticatable $authorization, string $flowId, string $code): DelightfulFlowApiKeyEntity
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        return $this->delightfulFlowApiKeyDomainService->changeSecretKey($dataIsolation, $code, $authorization->getId());
    }

    public function getByCode(Authenticatable $authorization, string $flowId, string $code): ?DelightfulFlowApiKeyEntity
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        return $this->delightfulFlowApiKeyDomainService->getByCode($dataIsolation, $code, $authorization->getId());
    }

    /**
     * @return array{total: int, list: array<DelightfulFlowApiKeyEntity>}
     */
    public function queries(Authenticatable $authorization, DelightfulFlowApiKeyQuery $query, Page $page): array
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        return $this->delightfulFlowApiKeyDomainService->queries($dataIsolation, $query, $page);
    }

    public function destroy(Authenticatable $authorization, string $code): void
    {
        $dataIsolation = $this->createFlowDataIsolation($authorization);
        $this->delightfulFlowApiKeyDomainService->destroy($dataIsolation, $code, $authorization->getId());
    }
}
