<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Factory;

use DateTime;
use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BuiltinAgent;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentType;

class BuiltinAgentFactory
{
    /**
     * 创建内置智能体实体.
     */
    public static function createEntity(BuiltinAgent $builtinAgent, string $organizationCode): BeDelightfulAgentEntity
    {
        $entity = new BeDelightfulAgentEntity();

        // 设置基本信息
        $entity->setOrganizationCode($organizationCode);
        $entity->setCode($builtinAgent->value);
        $entity->setName($builtinAgent->getName());
        $entity->setDescription($builtinAgent->getDescription());
        $entity->setIcon($builtinAgent->getIcon());
        $entity->setType(BeDelightfulAgentType::Built_In);
        $entity->setEnabled(true);
        $entity->setPrompt($builtinAgent->getPrompt());
        $entity->setTools([]);

        // 设置系统创建信息
        $entity->setCreator('system');
        $entity->setCreatedAt(new DateTime());
        $entity->setModifier('system');
        $entity->setUpdatedAt(new DateTime());

        return $entity;
    }

    /**
     * 创建所有内置智能体实体.
     * @return array<BeDelightfulAgentEntity>
     */
    public static function createAllBuiltinEntities(string $organizationCode): array
    {
        $entities = [];

        foreach (BuiltinAgent::getAllBuiltinAgents() as $builtinAgent) {
            $entities[] = self::createEntity($builtinAgent, $organizationCode);
        }

        return $entities;
    }
}
