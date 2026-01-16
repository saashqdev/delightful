<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Event;

use Delightful\BeDelightful\Domain\Agent\Entity\BeDelightfulAgentEntity;

class BeDelightfulAgentdelete dEvent 
{
 
    public function __construct(
    public BeDelightfulAgentEntity $BeDelightfulAgentEntity) 
{
 
}
 
}
 
