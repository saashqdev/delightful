<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Assembler\Knowledge;

use App\Domain\KnowledgeBase\Entity\KnowledgeBaseEntity;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Kernel\Assembler\OperatorAssembler;
use App\Interfaces\Kernel\DTO\PageDTO;
use App\Interfaces\KnowledgeBase\DTO\KnowledgeBaseListDTO;

class DelightfulFlowKnowledgeAssembler
{
    /**
     * @param array<KnowledgeBaseEntity> $list
     */
    public static function createPageListDTO(int $total, array $list, Page $page, array $users): PageDTO
    {
        $list = array_map(fn (KnowledgeBaseEntity $entity) => self::createListDTO($entity, $users), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }

    protected static function createListDTO(KnowledgeBaseEntity $delightfulFlowKnowledgeEntity, array $users): KnowledgeBaseListDTO
    {
        $listDTO = new KnowledgeBaseListDTO($delightfulFlowKnowledgeEntity->toArray());
        $listDTO->setId($delightfulFlowKnowledgeEntity->getCode());
        $listDTO->setCreator($delightfulFlowKnowledgeEntity->getCreator());
        $listDTO->setCreatedAt($delightfulFlowKnowledgeEntity->getCreatedAt());
        $listDTO->setModifier($delightfulFlowKnowledgeEntity->getModifier());
        $listDTO->setUpdatedAt($delightfulFlowKnowledgeEntity->getUpdatedAt());
        $listDTO->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowKnowledgeEntity->getCreator()] ?? null, $delightfulFlowKnowledgeEntity->getCreatedAt()));
        $listDTO->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowKnowledgeEntity->getModifier()] ?? null, $delightfulFlowKnowledgeEntity->getUpdatedAt()));
        $listDTO->setUserOperation($delightfulFlowKnowledgeEntity->getUserOperation());
        $listDTO->setExpectedNum($delightfulFlowKnowledgeEntity->getExpectedNum());
        $listDTO->setCompletedNum($delightfulFlowKnowledgeEntity->getCompletedNum());
        return $listDTO;
    }
}
