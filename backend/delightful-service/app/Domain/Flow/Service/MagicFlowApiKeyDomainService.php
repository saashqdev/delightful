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
        private readonly DelightfulFlowApiKeyRepositoryInterface $magicFlowApiKeyRepository
    ) {
    }

    public function save(FlowDataIsolation $dataIsolation, DelightfulFlowApiKeyEntity $savingDelightfulFlowApiKeyEntity): DelightfulFlowApiKeyEntity
    {
        $savingDelightfulFlowApiKeyEntity->setCreator($dataIsolation->getCurrentUserId());
        $savingDelightfulFlowApiKeyEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        if ($savingDelightfulFlowApiKeyEntity->shouldCreate()) {
            $savingDelightfulFlowApiKeyEntity->prepareForCreate();
            $magicFlowApiKeyEntity = $savingDelightfulFlowApiKeyEntity;
            // 检查是否重复，毕竟是需要一对一的关系
            /* @phpstan-ignore-next-line */
            if ($magicFlowApiKeyEntity->getType() === ApiKeyType::Personal) {
                if ($this->magicFlowApiKeyRepository->exist($dataIsolation, $magicFlowApiKeyEntity)) {
                    ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.exist', ['label' => 'flow.fields.api_key']);
                }
            }
        } else {
            $magicFlowApiKeyEntity = $this->magicFlowApiKeyRepository->getByCode($dataIsolation, $savingDelightfulFlowApiKeyEntity->getCode());
            if (! $magicFlowApiKeyEntity) {
                ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $savingDelightfulFlowApiKeyEntity->getCode()]);
            }
            $savingDelightfulFlowApiKeyEntity->prepareForModification($magicFlowApiKeyEntity);
        }

        return $this->magicFlowApiKeyRepository->save($dataIsolation, $magicFlowApiKeyEntity);
    }

    public function changeSecretKey(FlowDataIsolation $dataIsolation, string $code, ?string $operator = null): DelightfulFlowApiKeyEntity
    {
        // 只能修改自己的
        $magicFlowApiKeyEntity = $this->magicFlowApiKeyRepository->getByCode($dataIsolation, $code, $operator);
        if (! $magicFlowApiKeyEntity) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $code]);
        }
        $magicFlowApiKeyEntity->prepareForUpdateSecretKey();
        return $this->magicFlowApiKeyRepository->save($dataIsolation, $magicFlowApiKeyEntity);
    }

    public function getByCode(FlowDataIsolation $dataIsolation, string $code, ?string $operator = null): ?DelightfulFlowApiKeyEntity
    {
        $magicFlowApiKeyEntity = $this->magicFlowApiKeyRepository->getByCode($dataIsolation, $code, $operator);
        if (! $magicFlowApiKeyEntity) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $code]);
        }
        return $magicFlowApiKeyEntity;
    }

    public function getBySecretKey(FlowDataIsolation $dataIsolation, string $secretKey): DelightfulFlowApiKeyEntity
    {
        $magicFlowApiKeyEntity = $this->magicFlowApiKeyRepository->getBySecretKey($dataIsolation, $secretKey);
        if (! $magicFlowApiKeyEntity) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $secretKey]);
        }
        return $magicFlowApiKeyEntity;
    }

    /**
     * @return array{total: int, list: array<DelightfulFlowApiKeyEntity>}
     */
    public function queries(FlowDataIsolation $dataIsolation, DelightfulFlowApiKeyQuery $query, Page $page): array
    {
        return $this->magicFlowApiKeyRepository->queries($dataIsolation, $query, $page);
    }

    public function destroy(FlowDataIsolation $dataIsolation, string $code, ?string $operator = null): void
    {
        $magicFlowApiKeyEntity = $this->magicFlowApiKeyRepository->getByCode($dataIsolation, $code, $operator);
        if (! $magicFlowApiKeyEntity) {
            ExceptionBuilder::throw(FlowErrorCode::ValidateFailed, 'common.not_found', ['label' => $code]);
        }

        $this->magicFlowApiKeyRepository->destroy($dataIsolation, $magicFlowApiKeyEntity->getCode());
    }
}
