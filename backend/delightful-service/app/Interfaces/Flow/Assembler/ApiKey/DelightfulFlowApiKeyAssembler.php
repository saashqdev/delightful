<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Assembler\ApiKey;

use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Flow\Entity\DelightfulFlowApiKeyEntity;
use App\Domain\Flow\Entity\ValueObject\ApiKeyType;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Flow\DTO\ApiKey\DelightfulFlowApiKeyDTO;
use App\Interfaces\Kernel\Assembler\OperatorAssembler;
use App\Interfaces\Kernel\DTO\PageDTO;

class DelightfulFlowApiKeyAssembler
{
    public static function createFlowApiKeyDTOByMixed(mixed $data): ?DelightfulFlowApiKeyDTO
    {
        if ($data instanceof DelightfulFlowApiKeyDTO) {
            return $data;
        }
        if (is_array($data)) {
            return new DelightfulFlowApiKeyDTO($data);
        }
        return null;
    }

    public static function createDO(DelightfulFlowApiKeyDTO $apiKeyDTO): DelightfulFlowApiKeyEntity
    {
        $entity = new DelightfulFlowApiKeyEntity();
        $entity->setCode($apiKeyDTO->getId() ?? '');
        $entity->setFlowCode($apiKeyDTO->getFlowCode());
        $entity->setType(ApiKeyType::from($apiKeyDTO->getType()));
        $entity->setName($apiKeyDTO->getName());
        $entity->setDescription($apiKeyDTO->getDescription());
        $entity->setConversationId($apiKeyDTO->getConversationId());
        $entity->setEnabled($apiKeyDTO->isEnabled());
        return $entity;
    }

    /**
     * @param array<string, DelightfulUserEntity> $users
     */
    public static function createDTO(DelightfulFlowApiKeyEntity $delightfulFlowApiKeyEntity, array $users = []): DelightfulFlowApiKeyDTO
    {
        $DTO = new DelightfulFlowApiKeyDTO($delightfulFlowApiKeyEntity->toArray());
        $DTO->setId($delightfulFlowApiKeyEntity->getCode());
        $DTO->setCreator($delightfulFlowApiKeyEntity->getCreator());
        $DTO->setCreatedAt($delightfulFlowApiKeyEntity->getCreatedAt());
        $DTO->setModifier($delightfulFlowApiKeyEntity->getModifier());
        $DTO->setUpdatedAt($delightfulFlowApiKeyEntity->getUpdatedAt());
        $DTO->setCreatorInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowApiKeyEntity->getCreator()] ?? null, $delightfulFlowApiKeyEntity->getCreatedAt()));
        $DTO->setModifierInfo(OperatorAssembler::createOperatorDTOByUserEntity($users[$delightfulFlowApiKeyEntity->getModifier()] ?? null, $delightfulFlowApiKeyEntity->getUpdatedAt()));
        return $DTO;
    }

    /**
     * @param DelightfulFlowApiKeyEntity[] $list
     */
    public static function createPageListDTO(int $total, array $list, Page $page, array $users = []): PageDTO
    {
        $list = array_map(fn (DelightfulFlowApiKeyEntity $entity) => self::createDTO($entity, $users), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }
}
