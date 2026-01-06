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
        $list = array_map(fn (DelightfulFlowDraftEntity $magicFlowDraftEntity) => self::createDelightfulFlowDraftListDTO($magicFlowDraftEntity, $users), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }

    public static function createDelightfulFlowDraftDO(DelightfulFlowDraftDTO $magicFlowDraftDTO): DelightfulFlowDraftEntity
    {
        $magicFlowDraft = new DelightfulFlowDraftEntity();
        $magicFlowDraft->setFlowCode($magicFlowDraftDTO->getFlowCode());
        $magicFlowDraft->setCode((string) $magicFlowDraftDTO->getId());
        $magicFlowDraft->setName($magicFlowDraftDTO->getName());
        $magicFlowDraft->setDescription($magicFlowDraftDTO->getDescription());
        $magicFlowDraft->setDelightfulFlow($magicFlowDraftDTO->getDelightfulFLow());
        return $magicFlowDraft;
    }

    public static function createDelightfulFlowDraftDTO(DelightfulFlowDraftEntity $magicFlowDraft, array $users = [], array $icons = []): DelightfulFlowDraftDTO
    {
        $dto = new DelightfulFlowDraftDTO($magicFlowDraft->toArray());
        $dto->setId($magicFlowDraft->getCode());
        $dto->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$magicFlowDraft->getCreator()] ?? null, $magicFlowDraft->getCreatedAt()));
        $dto->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$magicFlowDraft->getModifier()] ?? null, $magicFlowDraft->getUpdatedAt()));
        if (isset($dto->getDelightfulFLow()['icon'])) {
            $dto->getDelightfulFLow()['icon'] = FileAssembler::getUrl($icons[$magicFlowDraft->getDelightfulFlow()['icon']] ?? null);
        }
        return $dto;
    }

    protected static function createDelightfulFlowDraftListDTO(DelightfulFlowDraftEntity $magicFlowDraftEntity, array $users = []): DelightfulFlowDraftListDTO
    {
        $dto = new DelightfulFlowDraftListDTO($magicFlowDraftEntity->toArray());
        $dto->setId($magicFlowDraftEntity->getCode());
        $dto->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$magicFlowDraftEntity->getCreator()] ?? null, $magicFlowDraftEntity->getCreatedAt()));
        $dto->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$magicFlowDraftEntity->getModifier()] ?? null, $magicFlowDraftEntity->getUpdatedAt()));
        return $dto;
    }
}
