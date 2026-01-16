<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
/** * delete topic before event - triggered before topic deletion to handle sandbox termination. */

class delete TopicBeforeEvent extends AbstractEvent 
{
 
    public function __construct( 
    private string $organizationCode, 
    private string $userId, 
    private int $topicId, 
    private TopicEntity $topicEntity, ) 
{
 // Call parent constructor to generate snowflake ID parent::__construct(); 
}
 
    public function getOrganizationCode(): string 
{
 return $this->organizationCode; 
}
 
    public function getuser Id(): string 
{
 return $this->userId; 
}
 
    public function getTopicId(): int 
{
 return $this->topicId; 
}
 
    public function getTopicEntity(): TopicEntity 
{
 return $this->topicEntity; 
}
 
}
 
