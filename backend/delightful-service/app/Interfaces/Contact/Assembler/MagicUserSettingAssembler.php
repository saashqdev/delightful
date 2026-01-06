<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Contact\Assembler;

use App\Domain\Contact\Entity\DelightfulUserSettingEntity;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Contact\DTO\DelightfulUserSettingDTO;
use App\Interfaces\Kernel\DTO\PageDTO;

class DelightfulUserSettingAssembler
{
    public static function createEntity(DelightfulUserSettingDTO $dto): DelightfulUserSettingEntity
    {
        $entity = new DelightfulUserSettingEntity();
        $entity->setKey($dto->getKey());
        $entity->setValue($dto->getValue());
        return $entity;
    }

    public static function createDTO(DelightfulUserSettingEntity $entity): DelightfulUserSettingDTO
    {
        $dto = new DelightfulUserSettingDTO();
        $dto->setId($entity->getId());
        $dto->setKey($entity->getKey());
        $dto->setValue($entity->getValue());
        $dto->setCreatedAt($entity->getCreatedAt()->format('Y-m-d H:i:s'));
        $dto->setUpdatedAt($entity->getUpdatedAt()->format('Y-m-d H:i:s'));

        return $dto;
    }

    /**
     * @param array<DelightfulUserSettingEntity> $list
     */
    public static function createPageListDTO(int $total, array $list, Page $page): PageDTO
    {
        $list = array_map(
            static fn (DelightfulUserSettingEntity $entity) => self::createDTO($entity),
            $list
        );
        return new PageDTO($page->getPage(), $total, $list);
    }
}
