<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Service;

use App\Domain\Flow\Entity\DelightfulFlowApiKeyEntity;
use App\Domain\Flow\Entity\ValueObject\ApiKeyType;
use App\Domain\Flow\Entity\ValueObject\FlowDataIsolation;
use App\Domain\Flow\Entity\ValueObject\Query\DelightfulFlowApiKeyQuery;
use App\Domain\Flow\Repository\Facade\DelightfulFlowApiKeyRepositoryInterface;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;

class DelightfulFlowApiKeyDomainService extends AbstractDomainService
{
    public function __construct(
        private readonly DelightfulFlowApiKeyRepositoryInterface $delightfulFlowApiKeyRepository
    ) {
    }

    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowApiKeyEntity $savingDelightfulFlowApiKeyEntity): DelightfulFlowApiKeyEntity
    {
        $savingDelightfulFlowApiKeyEntity->setCreator($dataIsolation->getCurrentUserId());
        $savingDelightfulFlowApiKeyEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        if ($savingDelightfulFlowApiKeyEntity->shouldCreate()) {
            $savingDelightfulFlowApiKeyEntity->prepareForCreate();
            $delightfulFlowApiKeyEntity = $savingDelightfulFlowApiKeyEntity;
            // checkwhether重复，毕竟isneedonetooneclose系
            /* @phpstan-ignore-next-line */
            if ($delightfulFlowApiKeyEntity->getType() === ApiKeyType::Personal) {
                if ($this->delightfulFlowApiKeyRepository->exist($dataIsolation, $delightfulFlowApiKeyEntity)) {
                    ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.exist', ['label' => 'flow.fields.api_key']);
                }
            }
        } else {
            $delightfulFlowApiKeyEntity = $this->delightfulFlowApiKeyRepository->getByCode($dataIsolation, $savingDelightfulFlowApiKeyEntity->getCode());
            if (! $delightfulFlowApiKeyEntity) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $savingDelightfulFlowApiKeyEntity->getCode()]);
            }
            $savingDelightfulFlowApiKeyEntity->prepareForModification($delightfulFlowApiKeyEntity);
        }

        return $this->delightfulFlowApiKeyRepository->save($dataIsolation, $delightfulFlowApiKeyEntity);
    }

    public function changeSecretKey(FlowDataIsolation $dataIsolation, string $code, ?string $operator = null): DelightfulFlowApiKeyEntity
    {
        // 只能modifyfrom己
        $delightfulFlowApiKeyEntity = $this->delightfulFlowApiKeyRepository->getByCode($dataIsolation, $code, $operator);
        if (! $delightfulFlowApiKeyEntity) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $code]);
        }
        $delightfulFlowApiKeyEntity->prepareForUpdateSecretKey();
        return $this->delightfulFlowApiKeyRepository->save($dataIsolation, $delightfulFlowApiKeyEntity);
    }

    public function getByCode(FlowDataIsolation $dataIsolation, string $code, ?string $operator = null): ?DelightfulFlowApiKeyEntity
    {
        $delightfulFlowApiKeyEntity = $this->delightfulFlowApiKeyRepository->getByCode($dataIsolation, $code, $operator);
        if (! $delightfulFlowApiKeyEntity) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $code]);
        }
        return $delightfulFlowApiKeyEntity;
    }

    public function getBySecretKey(FlowDataIsolation $dataIsolation, string $secretKey): DelightfulFlowApiKeyEntity
    {
        $delightfulFlowApiKeyEntity = $this->delightfulFlowApiKeyRepository->getBySecretKey($dataIsolation, $secretKey);
        if (! $delightfulFlowApiKeyEntity) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $secretKey]);
        }
        return $delightfulFlowApiKeyEntity;
    }

    /**
     * @return array{total: int, list: array<DelightfulFlowApiKeyEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowApiKeyQuery $query, Page $page): array
    {
        return $this->delightfulFlowApiKeyRepository->queries($dataIsolation, $query, $page);
    }

    public function destroy(FlowDataIsolation $dataIsolation, string $code, ?string $operator = null): void
    {
        $delightfulFlowApiKeyEntity = $this->delightfulFlowApiKeyRepository->getByCode($dataIsolation, $code, $operator);
        if (! $delightfulFlowApiKeyEntity) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $code]);
        }

        $this->delightfulFlowApiKeyRepository->destroy($dataIsolation, $delightfulFlowApiKeyEntity->getCode());
    }
}
