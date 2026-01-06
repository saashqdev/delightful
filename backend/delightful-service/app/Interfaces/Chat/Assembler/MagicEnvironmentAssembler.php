<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Assembler;

use App\Domain\OrganizationEnvironment\Entity\DelightfulOrganizationEnvEntity;

class DelightfulEnvironmentAssembler
{
    public static function getDelightfulOrganizationEnvEntity(array $magicOrganizationEnv): DelightfulOrganizationEnvEntity
    {
        $magicOrganizationEnvEntity = new DelightfulOrganizationEnvEntity();
        $magicOrganizationEnvEntity->setId($magicOrganizationEnv['id']);
        $magicOrganizationEnvEntity->setLoginCode($magicOrganizationEnv['login_code']);
        $magicOrganizationEnvEntity->setDelightfulOrganizationCode($magicOrganizationEnv['magic_organization_code']);
        $magicOrganizationEnvEntity->setOriginOrganizationCode($magicOrganizationEnv['origin_organization_code']);
        $magicOrganizationEnvEntity->setEnvironmentId($magicOrganizationEnv['environment_id']);
        $magicOrganizationEnvEntity->setCreatedAt($magicOrganizationEnv['created_at']);
        $magicOrganizationEnvEntity->setUpdatedAt($magicOrganizationEnv['updated_at']);
        return $magicOrganizationEnvEntity;
    }
}
