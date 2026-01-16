<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectEntity;
/** * ItemMemberUpdatedEvent. */

class ProjectMembersUpdatedEvent extends AbstractEvent 
{
 /** * @param array $currentMembers current Memberlist */ 
    public function __construct( 
    private readonly ProjectEntity $projectEntity, 
    private readonly array $currentMembers, 
    private readonly Magicuser Authorization $userAuthorization ) 
{
 parent::__construct(); 
}
 
    public function getProjectEntity(): ProjectEntity 
{
 return $this->projectEntity; 
}
 
    public function getcurrent Members(): array 
{
 return $this->currentMembers; 
}
 
    public function getuser Authorization(): Magicuser Authorization 
{
 return $this->userAuthorization; 
}
 
}
 
