<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Assembler;

use App\Domain\Contact\Entity\DelightfulDepartmentEntity;
use App\Domain\Contact\Entity\DelightfulThirdPlatformDepartmentEntity;
use App\Domain\Contact\Entity\DelightfulThirdPlatformIdMappingEntity;

class DepartmentAssembler
{
    public static function getDepartmentEntity(array $department): DelightfulDepartmentEntity
    {
        return new DelightfulDepartmentEntity($department);
    }

    public static function getDelightfulThirdPlatformIdMappingEntity(array $thirdPlatformIdMapping): DelightfulThirdPlatformIdMappingEntity
    {
        return new DelightfulThirdPlatformIdMappingEntity($thirdPlatformIdMapping);
    }

    public static function getThirdPlatformDepartmentEntity(array $thirdPlatformDepartment): DelightfulThirdPlatformDepartmentEntity
    {
        return new DelightfulThirdPlatformDepartmentEntity($thirdPlatformDepartment);
    }
}
