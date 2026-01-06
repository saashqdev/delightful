<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Repository\Facade;

use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;

interface EnvironmentRepositoryInterface
{
    public function getEnvById(string $id): ?DelightfulEnvironmentEntity;

    /**
     * @return DelightfulEnvironmentEntity[]
     */
    public function getDelightfulEnvironments(): array;

    /**
     * @return DelightfulEnvironmentEntity[]
     */
    public function getDelightfulEnvironmentsByIds(array $ids): array;

    public function getDelightfulEnvironmentById(int $envId): ?DelightfulEnvironmentEntity;

    public function createDelightfulEnvironment(DelightfulEnvironmentEntity $environmentDTO): DelightfulEnvironmentEntity;

    public function updateDelightfulEnvironment(DelightfulEnvironmentEntity $environmentDTO): DelightfulEnvironmentEntity;

    public function getEnvironmentEntityByLoginCode(string $loginCode): ?DelightfulEnvironmentEntity;
}
