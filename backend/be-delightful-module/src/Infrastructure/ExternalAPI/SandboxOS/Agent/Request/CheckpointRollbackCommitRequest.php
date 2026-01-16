<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

/** * check pointRollbackSubmitRequestClass * Followsandbox DocumentationcheckpointRollbackSubmitRequestFormat. */

class check pointRollbackCommitRequest 
{
 /** * CreatecheckpointRollbackSubmitRequestObject */ 
    public 
    static function create(): self 
{
 return new self(); 
}
 /** * Convert toAPIRequestArray * According tosandbox DocumentationcheckpointRollbackSubmitRequestFormatEmptyRequest. */ 
    public function toArray(): array 
{
 return []; 
}
 
}
 
