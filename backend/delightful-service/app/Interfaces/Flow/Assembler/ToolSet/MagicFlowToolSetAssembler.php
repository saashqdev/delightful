<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Assembler\ToolSet;

use App\Domain\Flow\Entity\DelightfulFlowToolSetEntity;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Flow\DTO\ToolSet\DelightfulFlowToolSetDTO;
use App\Interfaces\Kernel\Assembler\FileAssembler;
use App\Interfaces\Kernel\Assembler\OperatorAssembler;
use App\Interfaces\Kernel\DTO\PageDTO;
use Delightful\CloudFile\Kernel\Struct\FileLink;

class DelightfulFlowToolSetAssembler
{
    public static function createDTO(DelightfulFlowToolSetEntity $magicFlowToolSetEntity, array $icons = [], array $users = []): DelightfulFlowToolSetDTO
    {
        $DTO = new DelightfulFlowToolSetDTO();
        $DTO->setId($magicFlowToolSetEntity->getCode());
        $DTO->setName($magicFlowToolSetEntity->getName());
        $DTO->setDescription($magicFlowToolSetEntity->getDescription());
        $DTO->setIcon(FileAssembler::getUrl($icons[$magicFlowToolSetEntity->getIcon()] ?? null));
        $DTO->setEnabled($magicFlowToolSetEntity->getEnabled());
        $DTO->setCreator($magicFlowToolSetEntity->getCreator());
        $DTO->setCreatedAt($magicFlowToolSetEntity->getCreatedAt());
        $DTO->setModifier($magicFlowToolSetEntity->getModifier());
        $DTO->setUpdatedAt($magicFlowToolSetEntity->getUpdatedAt());
        $DTO->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$magicFlowToolSetEntity->getCreator()] ?? null, $magicFlowToolSetEntity->getCreatedAt()));
        $DTO->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$magicFlowToolSetEntity->getModifier()] ?? null, $magicFlowToolSetEntity->getUpdatedAt()));
        $DTO->setTools($magicFlowToolSetEntity->getTools());
        $DTO->setUserOperation($magicFlowToolSetEntity->getUserOperation());
        return $DTO;
    }

    public static function createDO(DelightfulFlowToolSetDTO $magicFlowToolSetDTO): DelightfulFlowToolSetEntity
    {
        $magicFlowToolSetEntity = new DelightfulFlowToolSetEntity();
        $magicFlowToolSetEntity->setCode((string) $magicFlowToolSetDTO->getId());
        $magicFlowToolSetEntity->setName($magicFlowToolSetDTO->getName());
        $magicFlowToolSetEntity->setDescription($magicFlowToolSetDTO->getDescription());
        $magicFlowToolSetEntity->setIcon(FileAssembler::formatPath($magicFlowToolSetDTO->getIcon()));
        $magicFlowToolSetEntity->setEnabled($magicFlowToolSetDTO->getEnabled());
        return $magicFlowToolSetEntity;
    }

    /**
     * @param array<string, FileLink> $icons
     */
    public static function createPageListDTO(int $total, array $list, Page $page, array $users = [], array $icons = []): PageDTO
    {
        $list = array_map(fn (DelightfulFlowToolSetEntity $magicFlowToolSetEntity) => self::createDTO($magicFlowToolSetEntity, $icons, $users), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }
}
