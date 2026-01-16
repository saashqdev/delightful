<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

class Attachmentsprocess edEvent 
{
 
    public function __construct( 
    public int $parentFileId, 
    public int $projectId, 
    public int $taskId = 0 ) 
{
 
}
 
}
 
