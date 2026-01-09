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
    public function getOrganizationEnvironmentByDelightfulOrganizationCode(string $delightfulOrganizationCode): ?DelightfulOrganizationEnvEntity;

    public function getOrganizationEnvironmentByOrganizationCode(string $originOrganizationCode, DelightfulEnvironmentEntity $delightfulEnvironmentEntity): ?DelightfulOrganizationEnvEntity;

    public function createOrganizationEnvironment(DelightfulOrganizationEnvEntity $delightfulOrganizationEnvEntity): void;

    /**
     * @param string[] $delightfulOrganizationCodes
     * @return DelightfulOrganizationEnvEntity[]
     */
    public function getOrganizationEnvironments(array $delightfulOrganizationCodes, DelightfulEnvironmentEntity $delightfulEnvironmentEntity): array;

    /**
     * get所有organization编码
     * @return string[]
     */
    public function getAllOrganizationCodes(): array;

    public function getOrganizationEnvironmentByThirdPartyOrganizationCode(string $thirdPartyOrganizationCode, DelightfulEnvironmentEntity $delightfulEnvironmentEntity): ?DelightfulOrganizationEnvEntity;
}
