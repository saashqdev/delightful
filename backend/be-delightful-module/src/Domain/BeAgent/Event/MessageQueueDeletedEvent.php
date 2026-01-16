<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

use Delightful\BeDelightful\Domain\SuperAgent\Entity\MessageQueueEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
/** * Message Queue delete d Event. */

class MessageQueuedelete dEvent extends AbstractEvent 
{
 
    public function __construct( 
    private readonly MessageQueueEntity $messageQueueEntity, 
    private readonly TopicEntity $topicEntity, 
    private readonly string $userId, 
    private readonly string $organizationCode, ) 
{
 parent::__construct(); 
}
 
    public function getMessageQueueEntity(): MessageQueueEntity 
{
 return $this->messageQueueEntity; 
}
 
    public function getTopicEntity(): TopicEntity 
{
 return $this->topicEntity; 
}
 
    public function getuser Id(): string 
{
 return $this->userId; 
}
 
    public function getOrganizationCode(): string 
{
 return $this->organizationCode; 
}
 
}
 
