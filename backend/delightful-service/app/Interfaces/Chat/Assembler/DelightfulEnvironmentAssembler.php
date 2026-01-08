<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Assembler;

use App\Domain\OrganizationEnvironment\Entity\DelightfulOrganizationEnvEntity;

class DelightfulEnvironmentAssembler
{
    public static function getDelightfulOrganizationEnvEntity(array $delightfulOrganizationEnv): DelightfulOrganizationEnvEntity
    {
        $delightfulOrganizationEnvEntity = new DelightfulOrganizationEnvEntity();
        $delightfulOrganizationEnvEntity->setId($delightfulOrganizationEnv['id']);
        $delightfulOrganizationEnvEntity->setLoginCode($delightfulOrganizationEnv['login_code']);
        $delightfulOrganizationEnvEntity->setDelightfulOrganizationCode($delightfulOrganizationEnv['delightful_organization_code']);
        $delightfulOrganizationEnvEntity->setOriginOrganizationCode($delightfulOrganizationEnv['origin_organization_code']);
        $delightfulOrganizationEnvEntity->setEnvironmentId($delightfulOrganizationEnv['environment_id']);
        $delightfulOrganizationEnvEntity->setCreatedAt($delightfulOrganizationEnv['created_at']);
        $delightfulOrganizationEnvEntity->setUpdatedAt($delightfulOrganizationEnv['updated_at']);
        return $delightfulOrganizationEnvEntity;
    }
}
