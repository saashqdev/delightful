<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Provider\Service;

use App\Domain\Provider\DTO\ProviderOriginalModelDTO;
use App\Domain\Provider\Entity\ProviderOriginalModelEntity;
use App\Domain\Provider\Entity\ValueObject\ProviderDataIsolation;
use App\Domain\Provider\Entity\ValueObject\ProviderOriginalModelType;
use App\Domain\Provider\Service\ProviderOriginalModelDomainService;
use App\Interfaces\Authorization\Web\MagicUserAuthorization;
use App\Interfaces\Provider\Assembler\ProviderAdminAssembler;

class AdminOriginModelAppService
{
    public function __construct(
        private ProviderOriginalModelDomainService $providerOriginalModelDomainService,
    ) {
    }

    /**
     * Get the list of original models.
     *
     * @return array<ProviderOriginalModelDTO>
     */
    public function list(MagicUserAuthorization $authorization): array
    {
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );
        $entities = $this->providerOriginalModelDomainService->list($dataIsolation);
        return ProviderAdminAssembler::originalModelEntitiesToDTOs($entities);
    }

    /**
     * Add an original model identifier.
     */
    public function create(MagicUserAuthorization $authorization, string $modelId): ProviderOriginalModelDTO
    {
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );

        $entity = new ProviderOriginalModelEntity();
        $entity->setModelId($modelId);
        $entity->setType(ProviderOriginalModelType::Custom);
        $entity->setOrganizationCode($dataIsolation->getCurrentOrganizationCode());

        $createdEntity = $this->providerOriginalModelDomainService->create($dataIsolation, $entity);

        return ProviderAdminAssembler::originalModelEntityToDTO($createdEntity);
    }

    /**
     * Delete an original model identifier.
     */
    public function delete(MagicUserAuthorization $authorization, string $id): void
    {
        $dataIsolation = ProviderDataIsolation::create(
            $authorization->getOrganizationCode(),
            $authorization->getId(),
        );
        $this->providerOriginalModelDomainService->delete($dataIsolation, $id);
    }
}
