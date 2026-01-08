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
        protected DelightfulOrganizationEnvDomainService $delightfulOrganizationEnvDomainService,
    ) {
    }

    /**
     * @return DelightfulEnvironmentEntity[]
     */
    public function getDelightfulEnvironments(array $ids): array
    {
        if (empty($ids)) {
            return $this->delightfulOrganizationEnvDomainService->getEnvironmentEntities();
        }
        return $this->delightfulOrganizationEnvDomainService->getEnvironmentEntitiesByIds($ids);
    }

    // 创建环境
    public function createDelightfulEnvironment(DelightfulEnvironmentEntity $environmentDTO): DelightfulEnvironmentEntity
    {
        return $this->delightfulOrganizationEnvDomainService->createEnvironment($environmentDTO);
    }

    // 更新环境
    public function updateDelightfulEnvironment(DelightfulEnvironmentEntity $environmentDTO): DelightfulEnvironmentEntity
    {
        return $this->delightfulOrganizationEnvDomainService->updateEnvironment($environmentDTO);
    }
}
