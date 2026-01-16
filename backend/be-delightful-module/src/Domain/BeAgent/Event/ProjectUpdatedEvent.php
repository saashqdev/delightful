<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectEntity;
/** * ItemUpdatedEvent. */

class ProjectUpdatedEvent extends AbstractEvent 
{
 
    public function __construct( 
    private readonly ProjectEntity $projectEntity, 
    private readonly Magicuser Authorization $userAuthorization ) 
{
 parent::__construct(); 
}
 
    public function getProjectEntity(): ProjectEntity 
{
 return $this->projectEntity; 
}
 
    public function getuser Authorization(): Magicuser Authorization 
{
 return $this->userAuthorization; 
}
 
}
 
