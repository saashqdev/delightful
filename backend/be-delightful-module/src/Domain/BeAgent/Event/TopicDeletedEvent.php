<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
/** * topic delete dEvent. */

class Topicdelete dEvent extends AbstractEvent 
{
 
    public function __construct( 
    private readonly TopicEntity $topicEntity, 
    private readonly Magicuser Authorization $userAuthorization ) 
{
 parent::__construct(); 
}
 
    public function getTopicEntity(): TopicEntity 
{
 return $this->topicEntity; 
}
 
    public function getuser Authorization(): Magicuser Authorization 
{
 return $this->userAuthorization; 
}
 
}
 
