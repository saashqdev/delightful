<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\SuperAgent\Event;

/** * topic Messageprocess Event. * NoticeHaveMessageneed process PassConcreteMessageContent. */

class TopicMessageprocess Event extends AbstractEvent 
{
 /** * Function. * * @param int $topicId topic ID * @param int $taskId TaskID */ 
    public function __construct( 
    private readonly int $topicId, 
    private readonly int $taskId = 0 ) 
{
 // Call parent constructor to generate snowflake ID parent::__construct(); 
}
 /** * FromArrayCreateEvent. */ 
    public 
    static function fromArray(array $data): self 
{
 return new self( topicId: (int) ($data['topic_id'] ?? 0), taskId: (int) ($data['task_id'] ?? 0) ); 
}
 /** * Convert toArray. */ 
    public function toArray(): array 
{
 return [ 'topic_id' => $this->topicId, 'task_id' => $this->taskId, 'event_id' => $this->getEventId(), ]; 
}
 /** * Gettopic ID. */ 
    public function getTopicId(): int 
{
 return $this->topicId; 
}
 /** * GetTaskID. */ 
    public function getTaskId(): int 
{
 return $this->taskId; 
}
 
}
 
