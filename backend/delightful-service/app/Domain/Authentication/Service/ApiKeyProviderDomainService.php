<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Authentication\Service;

use App\Domain\Authentication\Entity\ApiKeyProviderEntity;
use App\Domain\Authentication\Entity\ValueObject\AuthenticationDataIsolation;
use App\Domain\Authentication\Entity\ValueObject\Query\ApiKeyProviderQuery;
use App\Domain\Authentication\Event\ApiKeyValidatedEvent;
use App\Domain\Authentication\Repository\Facade\ApiKeyProviderRepositoryInterface;
use App\ErrorCode\AuthenticationErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\Page;
use DateTime;
use Delightful\AsyncEvent\AsyncEventUtil;

readonly class ApiKeyProviderDomainService
{
    public function __construct(
        private ApiKeyProviderRepositoryInterface $apiKeyProviderRepository
    ) {
    }

    public function save(AuthenticationDataIsolation $dataIsolation, ApiKeyProviderEntity $savingApiKeyProviderEntity): ApiKeyProviderEntity
    {
        $savingApiKeyProviderEntity->setCreator($dataIsolation->getCurrentUserId());
        $savingApiKeyProviderEntity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());
        if ($savingApiKeyProviderEntity->shouldCreate()) {
            $savingApiKeyProviderEntity->prepareForCreate();
            $apiKeyProviderEntity = $savingApiKeyProviderEntity;
        } else {
            $apiKeyProviderEntity = $this->apiKeyProviderRepository->getByCode($dataIsolation, $savingApiKeyProviderEntity->getCode(), $dataIsolation->getCurrentUserId());
            if (! $apiKeyProviderEntity) {
                ExceptionBuilder::throw(AuthenticationErrorCode::ValidateFailed, 'common.not_found', ['label' => $savingApiKeyProviderEntity->getCode()]);
            }
            $savingApiKeyProviderEntity->prepareForModification($apiKeyProviderEntity);
        }

        return $this->apiKeyProviderRepository->save($dataIsolation, $apiKeyProviderEntity);
    }

    public function changeSecretKey(AuthenticationDataIsolation $dataIsolation, string $code, ?string $operator = null): ApiKeyProviderEntity
    {
        // onlycanmodifyfromself
        $apiKeyProviderEntity = $this->apiKeyProviderRepository->getByCode($dataIsolation, $code, $operator);
        if (! $apiKeyProviderEntity) {
            ExceptionBuilder::throw(AuthenticationErrorCode::ValidateFailed, 'common.not_found', ['label' => $code]);
        }
        $apiKeyProviderEntity->prepareForUpdateSecretKey();
        return $this->apiKeyProviderRepository->save($dataIsolation, $apiKeyProviderEntity);
    }

    public function getByCode(AuthenticationDataIsolation $dataIsolation, string $code, ?string $operator = null): ?ApiKeyProviderEntity
    {
        $apiKeyProviderEntity = $this->apiKeyProviderRepository->getByCode($dataIsolation, $code, $operator);
        if (! $apiKeyProviderEntity) {
            ExceptionBuilder::throw(AuthenticationErrorCode::ValidateFailed, 'common.not_found', ['label' => $code]);
        }
        return $apiKeyProviderEntity;
    }

    public function verifySecretKey(AuthenticationDataIsolation $dataIsolation, string $secretKey): ApiKeyProviderEntity
    {
        $apiKeyProviderEntity = $this->apiKeyProviderRepository->getBySecretKey($dataIsolation, $secretKey);
        if (! $apiKeyProviderEntity) {
            ExceptionBuilder::throw(AuthenticationErrorCode::ValidateFailed, 'common.not_found', ['label' => $secretKey]);
        }
        if (! $apiKeyProviderEntity->isEnabled()) {
            ExceptionBuilder::throw(AuthenticationErrorCode::ValidateFailed, 'common.disabled', ['label' => $apiKeyProviderEntity->getCode()]);
        }

        // publishverifysuccessevent
        AsyncEventUtil::dispatch(new ApiKeyValidatedEvent($apiKeyProviderEntity));

        return $apiKeyProviderEntity;
    }

    /**
     * @return array{total: int, list: array<ApiKeyProviderEntity>}
     */
    public function queries(AuthenticationDataIsolation $dataIsolation, ApiKeyProviderQuery $query, Page $page): array
    {
        return $this->apiKeyProviderRepository->queries($dataIsolation, $query, $page);
    }

    public function destroy(AuthenticationDataIsolation $dataIsolation, string $code, ?string $operator = null): bool
    {
        $apiKeyProviderEntity = $this->apiKeyProviderRepository->getByCode($dataIsolation, $code, $operator);
        if (! $apiKeyProviderEntity) {
            ExceptionBuilder::throw(AuthenticationErrorCode::ValidateFailed, 'common.not_found', ['label' => $code]);
        }

        return $this->apiKeyProviderRepository->destroy($dataIsolation, $apiKeyProviderEntity->getCode());
    }

    public function updateLastUsed(AuthenticationDataIsolation $dataIsolation, ApiKeyProviderEntity $apiKeyProviderEntity): ApiKeyProviderEntity
    {
        $apiKeyProviderEntity->setLastUsed(new DateTime());
        return $this->apiKeyProviderRepository->save($dataIsolation, $apiKeyProviderEntity);
    }
}
