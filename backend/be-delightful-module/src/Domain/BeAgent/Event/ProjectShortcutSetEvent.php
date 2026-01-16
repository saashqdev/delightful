<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ProjectEntity;
/** * Itemshortcut Set Event. */

class ProjectShortcutSetEvent extends AbstractEvent 
{
 
    public function __construct( 
    private readonly ProjectEntity $projectEntity, 
    private readonly int $workspaceId, 
    private readonly Magicuser Authorization $userAuthorization ) 
{
 parent::__construct(); 
}
 
    public function getProjectEntity(): ProjectEntity 
{
 return $this->projectEntity; 
}
 
    public function getWorkspaceId(): int 
{
 return $this->workspaceId; 
}
 
    public function getuser Authorization(): Magicuser Authorization 
{
 return $this->userAuthorization; 
}
 
    public function getProjectId(): int 
{
 return $this->projectEntity->getId(); 
}
 
    public function getuser Id(): string 
{
 return $this->userAuthorization->getId(); 
}
 
    public function getOrganizationCode(): string 
{
 return $this->userAuthorization->getOrganizationCode(); 
}
 
}
 
