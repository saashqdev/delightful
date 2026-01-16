<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Factory;

use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;
use Delightful\BeDelightful\Domain\Agent\Entity\ValueObject\BeDelightfulAgentType;
use Delightful\BeDelightful\Domain\Agent\Repository\Persistence\Model\BeDelightfulAgentModel;

class BeDelightfulAgentFactory 
{
 
    public 
    static function createEntity(BeDelightfulAgentModel $model): BeDelightfulAgentEntity 
{
 $entity = new BeDelightfulAgentEntity(); // RequiredField if ($model->id !== null) 
{
 $entity->setId($model->id); 
}
 if ($model->organization_code !== null) 
{
 $entity->setOrganizationCode($model->organization_code); 
}
 if ($model->code !== null) 
{
 $entity->setCode($model->code); 
}
 if ($model->name !== null) 
{
 $entity->setName($model->name); 
}
 // OptionalFieldSet if ($model->description !== null && $model->description !== '') 
{
 $entity->setDescription($model->description); 
}
 if ($model->icon !== null && ! empty($model->icon)) 
{
 $entity->setIcon($model->icon); 
}
 if ($model->icon_type !== null) 
{
 $entity->setIconType($model->icon_type); 
}
 if ($model->prompt !== null) 
{
 $entity->setPrompt($model->prompt); 
}
 if ($model->tools !== null) 
{
 $entity->settool s($model->tools); 
}
 if ($model->type !== null) 
{
 $entity->setType(BeDelightfulAgentType::from($model->type)); 
}
 if ($model->enabled !== null) 
{
 $entity->setEnabled($model->enabled); 
}
 if ($model->creator !== null) 
{
 $entity->setcreator ($model->creator); 
}
 if ($model->created_at !== null) 
{
 $entity->setCreatedAt($model->created_at); 
}
 if ($model->modifier !== null) 
{
 $entity->setModifier($model->modifier); 
}
 if ($model->updated_at !== null) 
{
 $entity->setUpdatedAt($model->updated_at); 
}
 return $entity; 
}
 
}
 
