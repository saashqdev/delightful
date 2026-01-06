<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;
use App\Domain\OrganizationEnvironment\Service\DelightfulOrganizationEnvDomainService;

class DelightfulEnvironmentAppService extends AbstractAppService
{
    public function __construct(
        protected DelightfulOrganizationEnvDomainService $magicOrganizationEnvDomainService,
    ) {
    }

    /**
     * @return DelightfulEnvironmentEntity[]
     */
    public function getDelightfulEnvironments(array $ids): array
    {
        if (empty($ids)) {
            return $this->magicOrganizationEnvDomainService->getEnvironmentEntities();
        }
        return $this->magicOrganizationEnvDomainService->getEnvironmentEntitiesByIds($ids);
    }

    // 创建环境
    public function createDelightfulEnvironment(DelightfulEnvironmentEntity $environmentDTO): DelightfulEnvironmentEntity
    {
        return $this->magicOrganizationEnvDomainService->createEnvironment($environmentDTO);
    }

    // 更新环境
    public function updateDelightfulEnvironment(DelightfulEnvironmentEntity $environmentDTO): DelightfulEnvironmentEntity
    {
        return $this->magicOrganizationEnvDomainService->updateEnvironment($environmentDTO);
    }
}
