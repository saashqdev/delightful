<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Assembler\FlowVersion;

use App\Domain\Flow\Entity\DelightfulFlowVersionEntity;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Flow\Assembler\Flow\DelightfulFlowAssembler;
use App\Interfaces\Flow\DTO\FlowVersion\DelightfulFlowVersionDTO;
use App\Interfaces\Flow\DTO\FlowVersion\DelightfulFlowVersionListDTO;
use App\Interfaces\Kernel\Assembler\FileAssembler;
use App\Interfaces\Kernel\Assembler\OperatorAssembler;
use App\Interfaces\Kernel\DTO\PageDTO;
use DateTime;

class DelightfulFlowVersionAssembler
{
    /**
     * @param array<DelightfulFlowVersionEntity> $list
     */
    public static function createPageListDTO(int $total, array $list, Page $page, array $users = []): PageDTO
    {
        $list = array_map(fn (DelightfulFlowVersionEntity $magicFlowVersionEntity) => self::createDelightfulFlowVersionListDTO($magicFlowVersionEntity, $users), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }

    public static function createDelightfulFlowVersionDO(DelightfulFlowVersionDTO $magicFlowVersionDTO): DelightfulFlowVersionEntity
    {
        $entity = new DelightfulFlowVersionEntity();
        $entity->setFlowCode($magicFlowVersionDTO->getFlowCode());
        $entity->setCode((string) $magicFlowVersionDTO->getId());
        $entity->setName($magicFlowVersionDTO->getName());
        $entity->setDescription($magicFlowVersionDTO->getDescription());
        $entity->setDelightfulFlow(DelightfulFlowAssembler::createDelightfulFlowDO($magicFlowVersionDTO->getDelightfulFLow()));
        $entity->setCreatedAt(new DateTime());
        return $entity;
    }

    public static function createDelightfulFlowVersionDTO(DelightfulFlowVersionEntity $magicFlowVersionEntity, array $icons = []): DelightfulFlowVersionDTO
    {
        $dto = new DelightfulFlowVersionDTO($magicFlowVersionEntity->toArray());
        $dto->setId($magicFlowVersionEntity->getCode());
        $dto->getDelightfulFLow()->setId($magicFlowVersionEntity->getDelightfulFlow()->getCode());
        $dto->getDelightfulFLow()->setUserOperation($magicFlowVersionEntity->getDelightfulFlow()->getUserOperation());
        $dto->getDelightfulFLow()->setIcon(FileAssembler::getUrl($icons[$magicFlowVersionEntity->getDelightfulFlow()->getIcon()] ?? null));
        return $dto;
    }

    private static function createDelightfulFlowVersionListDTO(DelightfulFlowVersionEntity $magicFlowVersionEntity, array $users = []): DelightfulFlowVersionListDTO
    {
        $dto = new DelightfulFlowVersionListDTO($magicFlowVersionEntity->toArray());
        $dto->setId($magicFlowVersionEntity->getCode());
        $dto->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$magicFlowVersionEntity->getCreator()] ?? null, $magicFlowVersionEntity->getCreatedAt()));
        $dto->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$magicFlowVersionEntity->getModifier()] ?? null, $magicFlowVersionEntity->getUpdatedAt()));
        return $dto;
    }
}
