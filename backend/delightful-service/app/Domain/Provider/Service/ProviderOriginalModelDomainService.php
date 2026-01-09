<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service;

use App\Domain\Provider\Entity\ProviderOriginalModelEntity;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Repository\Facade\ProviderOriginalModelRepositoryInterface;
use App\ErrorCode\ServiceProviderErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

use function Hyperf\Translation\__;

readonly class ProviderOriginalModelDomainService
{
    public function __construct(
        private ProviderOriginalModelRepositoryInterface $providerOriginalModelRepository,
    ) {
    }

    public function create(ProviderDataIsolation $dataIsolation, ProviderOriginalModelEntity $providerOriginalModelEntity): ProviderOriginalModelEntity
    {
        // notcanduplicateadd,byorganization纬degree+modelId+typejudge,因forotherorganizationmaybealsowilladd,use额outsidemethod
        if ($this->providerOriginalModelRepository->exist($dataIsolation, $providerOriginalModelEntity->getModelId(), $providerOriginalModelEntity->getType())) {
            ExceptionBuilder::throw(ServiceProviderErrorCode::InvalidParameter, __('service_provider.original_model_already_exists'));
        }

        return $this->providerOriginalModelRepository->save($dataIsolation, $providerOriginalModelEntity);
    }

    public function delete(ProviderDataIsolation $dataIsolation, string $id): void
    {
        $this->providerOriginalModelRepository->delete($dataIsolation, $id);
    }

    /**
     * @return array<ProviderOriginalModelEntity>
     */
    public function list(ProviderDataIsolation $dataIsolation): array
    {
        return $this->providerOriginalModelRepository->list($dataIsolation);
    }
}
