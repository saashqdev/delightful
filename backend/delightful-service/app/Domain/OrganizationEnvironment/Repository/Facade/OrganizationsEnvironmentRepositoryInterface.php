<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Repository\Facade;

use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;
use App\Domain\OrganizationEnvironment\Entity\DelightfulOrganizationEnvEntity;

interface OrganizationsEnvironmentRepositoryInterface
{
    public function getOrganizationEnvironmentByDelightfulOrganizationCode(string $magicOrganizationCode): ?DelightfulOrganizationEnvEntity;

    public function getOrganizationEnvironmentByOrganizationCode(string $originOrganizationCode, DelightfulEnvironmentEntity $magicEnvironmentEntity): ?DelightfulOrganizationEnvEntity;

    public function createOrganizationEnvironment(DelightfulOrganizationEnvEntity $magicOrganizationEnvEntity): void;

    /**
     * @param string[] $magicOrganizationCodes
     * @return DelightfulOrganizationEnvEntity[]
     */
    public function getOrganizationEnvironments(array $magicOrganizationCodes, DelightfulEnvironmentEntity $magicEnvironmentEntity): array;

    /**
     * 获取所有组织编码
     * @return string[]
     */
    public function getAllOrganizationCodes(): array;

    public function getOrganizationEnvironmentByThirdPartyOrganizationCode(string $thirdPartyOrganizationCode, DelightfulEnvironmentEntity $magicEnvironmentEntity): ?DelightfulOrganizationEnvEntity;
}
