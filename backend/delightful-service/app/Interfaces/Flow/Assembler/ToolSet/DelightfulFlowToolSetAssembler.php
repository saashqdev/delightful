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
use BeDelightful\CloudFile\Kernel\Struct\FileLink;

class DelightfulFlowToolSetAssembler
{
    public static function createDTO(DelightfulFlowToolSetEntity $delightfulFlowToolSetEntity, array $icons = [], array $users = []): DelightfulFlowToolSetDTO
    {
        $DTO = new DelightfulFlowToolSetDTO();
        $DTO->setId($delightfulFlowToolSetEntity->getCode());
        $DTO->setName($delightfulFlowToolSetEntity->getName());
        $DTO->setDescription($delightfulFlowToolSetEntity->getDescription());
        $DTO->setIcon(FileAssembler::getUrl($icons[$delightfulFlowToolSetEntity->getIcon()] ?? null));
        $DTO->setEnabled($delightfulFlowToolSetEntity->getEnabled());
        $DTO->setCreator($delightfulFlowToolSetEntity->getCreator());
        $DTO->setCreatedAt($delightfulFlowToolSetEntity->getCreatedAt());
        $DTO->setModifier($delightfulFlowToolSetEntity->getModifier());
        $DTO->setUpdatedAt($delightfulFlowToolSetEntity->getUpdatedAt());
        $DTO->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowToolSetEntity->getCreator()] ?? null, $delightfulFlowToolSetEntity->getCreatedAt()));
        $DTO->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowToolSetEntity->getModifier()] ?? null, $delightfulFlowToolSetEntity->getUpdatedAt()));
        $DTO->setTools($delightfulFlowToolSetEntity->getTools());
        $DTO->setUserOperation($delightfulFlowToolSetEntity->getUserOperation());
        return $DTO;
    }

    public static function createDO(DelightfulFlowToolSetDTO $delightfulFlowToolSetDTO): DelightfulFlowToolSetEntity
    {
        $delightfulFlowToolSetEntity = new DelightfulFlowToolSetEntity();
        $delightfulFlowToolSetEntity->setCode((string) $delightfulFlowToolSetDTO->getId());
        $delightfulFlowToolSetEntity->setName($delightfulFlowToolSetDTO->getName());
        $delightfulFlowToolSetEntity->setDescription($delightfulFlowToolSetDTO->getDescription());
        $delightfulFlowToolSetEntity->setIcon(FileAssembler::formatPath($delightfulFlowToolSetDTO->getIcon()));
        $delightfulFlowToolSetEntity->setEnabled($delightfulFlowToolSetDTO->getEnabled());
        return $delightfulFlowToolSetEntity;
    }

    /**
     * @param array<string, FileLink> $icons
     */
    public static function createPageListDTO(int $total, array $list, Page $page, array $users = [], array $icons = []): PageDTO
    {
        $list = array_map(fn (DelightfulFlowToolSetEntity $delightfulFlowToolSetEntity) => self::createDTO($delightfulFlowToolSetEntity, $icons, $users), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }
}
