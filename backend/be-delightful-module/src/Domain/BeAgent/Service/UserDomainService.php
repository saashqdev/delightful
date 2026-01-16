<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Service;

use App\Domain\Contact\Entity\Magicuser Entity;
use App\Domain\Contact\Service\Magicuser DomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\user Authorization;

class user DomainService 
{
 
    public function __construct( 
    protected Magicuser DomainService $magicuser DomainService, ) 
{
 
}
 
    public function getuser Entity(string $userId): ?Magicuser Entity 
{
 return $this->magicuser DomainService->getuser ById($userId); 
}
 
    public function getuser Authorization(string $userId): ?user Authorization 
{
 $magicuser Entity = $this->getuser Entity($userId); return user Authorization::fromuser Entity($magicuser Entity); 
}
 
}
 
