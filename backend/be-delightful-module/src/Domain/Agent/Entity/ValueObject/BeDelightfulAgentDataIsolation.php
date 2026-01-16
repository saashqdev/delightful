<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Agent\Entity\ValueObject;

use App\Infrastructure\Core\DataIsolation\BaseDataIsolation;

class BeDelightfulAgentDataIsolation extends BaseDataIsolation 
{
 
    public 
    static function create(string $currentOrganizationCode = '', string $userId = ''): self 
{
 return new self($currentOrganizationCode, $userId); 
}
 
}
 
