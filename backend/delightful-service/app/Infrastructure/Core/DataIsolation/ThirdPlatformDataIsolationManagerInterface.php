<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\DataIsolation;

use App\Domain\OrganizationEnvironment\Entity\DelightfulEnvironmentEntity;

interface ThirdPlatformDataIsolationManagerInterface
{
    public function extends(DataIsolationInterface $parentDataIsolation): void;

    public function init(DataIsolationInterface $dataIsolation, DelightfulEnvironmentEntity $magicEnvironmentEntity): void;

    public function toArray(): array;
}
