<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TaskFileEntity;
/** * FileRenameEvent. */

class FileRenamedEvent extends AbstractEvent 
{
 
    public function __construct( 
    private readonly TaskFileEntity $fileEntity, 
    private readonly Magicuser Authorization $userAuthorization ) 
{
 parent::__construct(); 
}
 
    public function getFileEntity(): TaskFileEntity 
{
 return $this->fileEntity; 
}
 
    public function getuser Authorization(): Magicuser Authorization 
{
 return $this->userAuthorization; 
}
 
}
 
