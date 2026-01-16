<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

class delete TopicAfterEvent extends AbstractEvent 
{
 
    public function __construct( 
    private string $organizationCode, 
    private string $userId, 
    private int $topicId, ) 
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
 
}
 
