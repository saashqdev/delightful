<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Assembler\AIModel;

use App\Domain\Flow\Entity\DelightfulFlowAIModelEntity;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Flow\DTO\AIModel\DelightfulFlowAIModelDTO;
use App\Interfaces\Flow\DTO\DelightfulFlowEnabledAIModelDTO;
use App\Interfaces\Kernel\Assembler\FileAssembler;
use App\Interfaces\Kernel\Assembler\OperatorAssembler;
use App\Interfaces\Kernel\DTO\PageDTO;

class DelightfulFlowAIModelAssembler
{
    public static function createFlowAIModelDTOByMixed(mixed $data): ?DelightfulFlowAIModelDTO
    {
        if ($data instanceof DelightfulFlowAIModelDTO) {
            return $data;
        }
        if (is_array($data)) {
            return new DelightfulFlowAIModelDTO($data);
        }
        return null;
    }

    public static function createDO(DelightfulFlowAIModelDTO $dto): DelightfulFlowAIModelEntity
    {
        $entity = new DelightfulFlowAIModelEntity();
        $entity->setName($dto->getName());
        $entity->setLabel($dto->getLabel());
        $entity->setTags($dto->getTags());
        $entity->setModelName($dto->getModelName());
        $entity->setDefaultConfigs($dto->getDefaultConfigs());
        $entity->setEnabled($dto->isEnabled());
        $entity->setImplementation($dto->getImplementation());
        $entity->setImplementationConfig($dto->getImplementationConfig());
        $entity->setSupportEmbedding($dto->isSupportEmbedding());
        $entity->setVectorSize($dto->getVectorSize());
        $entity->setIcon(FileAssembler::formatPath($dto->getIcon()));
        $entity->setDisplay($dto->isDisplay());
        $entity->setMaxTokens($dto->getMaxTokens());
        $entity->setSupportMultiModal($dto->isSupportMultiModal());
        return $entity;
    }

    public static function createDTO(DelightfulFlowAIModelEntity $delightfulFlowAIModelEntity, array $users = []): DelightfulFlowAIModelDTO
    {
        $dto = new DelightfulFlowAIModelDTO($delightfulFlowAIModelEntity->toArray());
        $dto->setId($delightfulFlowAIModelEntity->getId());

        $dto->setCreator($delightfulFlowAIModelEntity->getCreatedUid());
        $dto->setCreatedAt($delightfulFlowAIModelEntity->getCreatedAt());
        $dto->setModifier($delightfulFlowAIModelEntity->getUpdatedUid());
        $dto->setUpdatedAt($delightfulFlowAIModelEntity->getUpdatedAt());
        $dto->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowAIModelEntity->getCreatedUid()] ?? null, $delightfulFlowAIModelEntity->getCreatedAt()));
        $dto->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowAIModelEntity->getUpdatedUid()] ?? null, $delightfulFlowAIModelEntity->getUpdatedAt()));
        return $dto;
    }

    /**
     * @param DelightfulFlowAIModelEntity[] $list
     */
    public static function createPageListDTO(int $total, array $list, Page $page): PageDTO
    {
        $list = array_map(fn (DelightfulFlowAIModelEntity $entity) => self::createDTO($entity), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }

    public static function createEnabledDTO(DelightfulFlowAIModelEntity $delightfulFlowAIModelEntity): DelightfulFlowEnabledAIModelDTO
    {
        $dto = new DelightfulFlowEnabledAIModelDTO($delightfulFlowAIModelEntity->toArray());
        $dto->setValue($delightfulFlowAIModelEntity->getName());
        $dto->setIcon($delightfulFlowAIModelEntity->getIcon());
        $dto->setVision($delightfulFlowAIModelEntity->isSupportMultiModal());
        $dto->setConfigs($delightfulFlowAIModelEntity->getDefaultConfigs());
        return $dto;
    }

    public static function createEnabledListDTO(array $list): array
    {
        $list = array_map(fn (DelightfulFlowAIModelEntity $entity) => self::createEnabledDTO($entity), $list);
        return [
            'models' => $list,
        ];
    }
}
