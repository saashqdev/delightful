<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Request;

/** * check pointRollbackUndoRequestClass * Followsandbox DocumentationcheckpointRollbackUndoRequestFormat. */

class check pointRollbackUndoRequest 
{
 /** * CreatecheckpointRollbackUndoRequestObject */ 
    public 
    static function create(): self 
{
 return new self(); 
}
 /** * Convert toAPIRequestArray * According tosandbox DocumentationcheckpointRollbackUndoRequestFormatEmptyRequest. */ 
    public function toArray(): array 
{
 return []; 
}
 
}
 
