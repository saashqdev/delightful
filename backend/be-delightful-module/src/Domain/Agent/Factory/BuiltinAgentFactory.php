<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Factory;

use DateTime;
use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BuiltinAgent;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentType;

class BuiltinAgentFactory 
{
 /** * CreateBuilt-in. */ 
    public 
    static function createEntity(BuiltinAgent $builtinAgent, string $organizationCode): BeDelightfulAgentEntity 
{
 $entity = new BeDelightfulAgentEntity(); // Set basic info $entity->setOrganizationCode($organizationCode); $entity->setCode($builtinAgent->value); $entity->setName($builtinAgent->getName()); $entity->setDescription($builtinAgent->getDescription()); $entity->setIcon($builtinAgent->getIcon()); $entity->setType(BeDelightfulAgentType::Built_In); $entity->setEnabled(true); $entity->setPrompt($builtinAgent->getPrompt()); $entity->settool s([]); // Set SystemCreateinfo $entity->setcreator ('system'); $entity->setCreatedAt(new DateTime()); $entity->setModifier('system'); $entity->setUpdatedAt(new DateTime()); return $entity; 
}
 /** * CreateAllBuilt-in. * @return array<BeDelightfulAgentEntity> */ 
    public 
    static function createAllBuiltinEntities(string $organizationCode): array 
{
 $entities = []; foreach (BuiltinAgent::getAllBuiltinAgents() as $builtinAgent) 
{
 $entities[] = self::createEntity($builtinAgent, $organizationCode); 
}
 return $entities; 
}
 
}
 
