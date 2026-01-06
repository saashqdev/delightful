<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Assembler\FlowDraft;

use App\Domain\Flow\Entity\DelightfulFlowDraftEntity;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Flow\DTO\FlowDraft\DelightfulFlowDraftDTO;
use App\Interfaces\Flow\DTO\FlowDraft\DelightfulFlowDraftListDTO;
use App\Interfaces\Kernel\Assembler\FileAssembler;
use App\Interfaces\Kernel\Assembler\OperatorAssembler;
use App\Interfaces\Kernel\DTO\PageDTO;

class DelightfulFlowDraftAssembler
{
    public static function createFlowDraftDTOByMixed(mixed $data): ?DelightfulFlowDraftDTO
    {
        if ($data instanceof DelightfulFlowDraftDTO) {
            return $data;
        }
        if (is_array($data)) {
            return new DelightfulFlowDraftDTO($data);
        }
        return null;
    }

    /**
     * @param array<DelightfulFlowDraftEntity> $list
     */
    public static function createPageListDTO(int $total, array $list, Page $page, array $users = []): PageDTO
    {
        $list = array_map(fn (DelightfulFlowDraftEntity $delightfulFlowDraftEntity) => self::createDelightfulFlowDraftListDTO($delightfulFlowDraftEntity, $users), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }

    public static function createDelightfulFlowDraftDO(DelightfulFlowDraftDTO $delightfulFlowDraftDTO): DelightfulFlowDraftEntity
    {
        $delightfulFlowDraft = new DelightfulFlowDraftEntity();
        $delightfulFlowDraft->setFlowCode($delightfulFlowDraftDTO->getFlowCode());
        $delightfulFlowDraft->setCode((string) $delightfulFlowDraftDTO->getId());
        $delightfulFlowDraft->setName($delightfulFlowDraftDTO->getName());
        $delightfulFlowDraft->setDescription($delightfulFlowDraftDTO->getDescription());
        $delightfulFlowDraft->setDelightfulFlow($delightfulFlowDraftDTO->getDelightfulFLow());
        return $delightfulFlowDraft;
    }

    public static function createDelightfulFlowDraftDTO(DelightfulFlowDraftEntity $delightfulFlowDraft, array $users = [], array $icons = []): DelightfulFlowDraftDTO
    {
        $dto = new DelightfulFlowDraftDTO($delightfulFlowDraft->toArray());
        $dto->setId($delightfulFlowDraft->getCode());
        $dto->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowDraft->getCreator()] ?? null, $delightfulFlowDraft->getCreatedAt()));
        $dto->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowDraft->getModifier()] ?? null, $delightfulFlowDraft->getUpdatedAt()));
        if (isset($dto->getDelightfulFLow()['icon'])) {
            $dto->getDelightfulFLow()['icon'] = FileAssembler::getUrl($icons[$delightfulFlowDraft->getDelightfulFlow()['icon']] ?? null);
        }
        return $dto;
    }

    protected static function createDelightfulFlowDraftListDTO(DelightfulFlowDraftEntity $delightfulFlowDraftEntity, array $users = []): DelightfulFlowDraftListDTO
    {
        $dto = new DelightfulFlowDraftListDTO($delightfulFlowDraftEntity->toArray());
        $dto->setId($delightfulFlowDraftEntity->getCode());
        $dto->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowDraftEntity->getCreator()] ?? null, $delightfulFlowDraftEntity->getCreatedAt()));
        $dto->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowDraftEntity->getModifier()] ?? null, $delightfulFlowDraftEntity->getUpdatedAt()));
        return $dto;
    }
}
