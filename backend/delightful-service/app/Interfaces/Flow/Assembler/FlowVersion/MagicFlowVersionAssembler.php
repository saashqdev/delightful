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
        $list = array_map(fn (DelightfulFlowVersionEntity $delightfulFlowVersionEntity) => self::createDelightfulFlowVersionListDTO($delightfulFlowVersionEntity, $users), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }

    public static function createDelightfulFlowVersionDO(DelightfulFlowVersionDTO $delightfulFlowVersionDTO): DelightfulFlowVersionEntity
    {
        $entity = new DelightfulFlowVersionEntity();
        $entity->setFlowCode($delightfulFlowVersionDTO->getFlowCode());
        $entity->setCode((string) $delightfulFlowVersionDTO->getId());
        $entity->setName($delightfulFlowVersionDTO->getName());
        $entity->setDescription($delightfulFlowVersionDTO->getDescription());
        $entity->setDelightfulFlow(DelightfulFlowAssembler::createDelightfulFlowDO($delightfulFlowVersionDTO->getDelightfulFLow()));
        $entity->setCreatedAt(new DateTime());
        return $entity;
    }

    public static function createDelightfulFlowVersionDTO(DelightfulFlowVersionEntity $delightfulFlowVersionEntity, array $icons = []): DelightfulFlowVersionDTO
    {
        $dto = new DelightfulFlowVersionDTO($delightfulFlowVersionEntity->toArray());
        $dto->setId($delightfulFlowVersionEntity->getCode());
        $dto->getDelightfulFLow()->setId($delightfulFlowVersionEntity->getDelightfulFlow()->getCode());
        $dto->getDelightfulFLow()->setUserOperation($delightfulFlowVersionEntity->getDelightfulFlow()->getUserOperation());
        $dto->getDelightfulFLow()->setIcon(FileAssembler::getUrl($icons[$delightfulFlowVersionEntity->getDelightfulFlow()->getIcon()] ?? null));
        return $dto;
    }

    private static function createDelightfulFlowVersionListDTO(DelightfulFlowVersionEntity $delightfulFlowVersionEntity, array $users = []): DelightfulFlowVersionListDTO
    {
        $dto = new DelightfulFlowVersionListDTO($delightfulFlowVersionEntity->toArray());
        $dto->setId($delightfulFlowVersionEntity->getCode());
        $dto->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowVersionEntity->getCreator()] ?? null, $delightfulFlowVersionEntity->getCreatedAt()));
        $dto->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowVersionEntity->getModifier()] ?? null, $delightfulFlowVersionEntity->getUpdatedAt()));
        return $dto;
    }
}
