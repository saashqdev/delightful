<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use App\Interfaces\Authorization\Web\Magicuser Authorization;
/** * FileBatchdelete Event. */

class FilesBatchdelete dEvent extends AbstractEvent 
{
 
    public function __construct( 
    private readonly int $projectId, 
    private readonly array $fileIds, 
    private readonly Magicuser Authorization $userAuthorization ) 
{
 parent::__construct(); 
}
 
    public function getProjectId(): int 
{
 return $this->projectId; 
}
 
    public function getFileIds(): array 
{
 return $this->fileIds; 
}
 
    public function getuser Authorization(): Magicuser Authorization 
{
 return $this->userAuthorization; 
}
 
}
 
