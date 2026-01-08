<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Assembler\TriggerTestcase;

use App\Domain\Flow\Entity\DelightfulFlowTriggerTestcaseEntity;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Flow\DTO\TriggerTestcase\DelightfulFlowTriggerTestcaseDTO;
use App\Interfaces\Kernel\Assembler\OperatorAssembler;
use App\Interfaces\Kernel\DTO\PageDTO;

class DelightfulFlowTriggerTestcaseAssembler
{
    /**
     * @param array<DelightfulFlowTriggerTestcaseEntity> $list
     */
    public static function createPageListDTO(int $total, array $list, Page $page, array $users = []): PageDTO
    {
        $list = array_map(fn (DelightfulFlowTriggerTestcaseEntity $delightfulFlowTriggerTestcaseEntity) => self::createDelightfulFlowTriggerTestcaseDTO($delightfulFlowTriggerTestcaseEntity, $users), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }

    public static function createDelightfulFlowTriggerTestcaseDO(DelightfulFlowTriggerTestcaseDTO $delightfulFlowTriggerTestcaseDTO): DelightfulFlowTriggerTestcaseEntity
    {
        $entity = new DelightfulFlowTriggerTestcaseEntity();
        $entity->setFlowCode($delightfulFlowTriggerTestcaseDTO->getFlowCode());
        $entity->setCode($delightfulFlowTriggerTestcaseDTO->getId());
        $entity->setName($delightfulFlowTriggerTestcaseDTO->getName());
        $entity->setDescription($delightfulFlowTriggerTestcaseDTO->getDescription());
        $entity->setCaseConfig($delightfulFlowTriggerTestcaseDTO->getCaseConfig());
        return $entity;
    }

    public static function createDelightfulFlowTriggerTestcaseDTO(DelightfulFlowTriggerTestcaseEntity $delightfulFlowTriggerTestcaseEntity, array $users = []): DelightfulFlowTriggerTestcaseDTO
    {
        $dto = new DelightfulFlowTriggerTestcaseDTO();
        $dto->setId($delightfulFlowTriggerTestcaseEntity->getCode());
        $dto->setName($delightfulFlowTriggerTestcaseEntity->getName());
        $dto->setDescription($delightfulFlowTriggerTestcaseEntity->getDescription());
        $dto->setCreator($delightfulFlowTriggerTestcaseEntity->getCreator());
        $dto->setCreatedAt($delightfulFlowTriggerTestcaseEntity->getCreatedAt());
        $dto->setModifier($delightfulFlowTriggerTestcaseEntity->getModifier());
        $dto->setUpdatedAt($delightfulFlowTriggerTestcaseEntity->getUpdatedAt());
        $dto->setFlowCode($delightfulFlowTriggerTestcaseEntity->getFlowCode());
        $dto->setCaseConfig($delightfulFlowTriggerTestcaseEntity->getCaseConfig());

        $dto->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowTriggerTestcaseEntity->getCreator()] ?? null, $delightfulFlowTriggerTestcaseEntity->getCreatedAt()));
        $dto->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowTriggerTestcaseEntity->getModifier()] ?? null, $delightfulFlowTriggerTestcaseEntity->getUpdatedAt()));

        return $dto;
    }
}
