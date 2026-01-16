<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe;

use Dtyq\AsyncEvent\Kernel\Annotation\Asynclist ener;
use Delightful\BeDelightful\Application\SuperAgent\Service\MessageQueueprocess AppService;
use Delightful\BeDelightful\Domain\SuperAgent\Event\RunTaskCallbackEvent;
use Hyperf\Event\Annotation\list ener;
use Hyperf\Event\Contract\list enerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
/** * Message Queue process Subscriber * list ens to RunTaskCallbackEvent and processes pending message queues when task is completed. */ #[Asynclist ener] #[list ener]

class MessageQueueprocess Subscriber implements list enerInterface 
{
 
    protected LoggerInterface $logger; 
    public function __construct() 
{
 $this->logger = di(LoggerFactory::class)->get(self::class); 
}
 /** * list en to events. */ 
    public function listen(): array 
{
 return [ RunTaskCallbackEvent::class, ]; 
}
 /** * process the event. */ 
    public function process(object $event): void 
{
 // Type check if (! $event instanceof RunTaskCallbackEvent) 
{
 return; 
}
 $this->logger->info('Received RunTaskCallbackEvent, processing message queue', [ 'topic_id' => $event->getTopicId(), 'task_id' => $event->getTaskId(), ]); try 
{
 // process message queue for this topic di(MessageQueueprocess AppService::class)->processTopicMessageQueue($event->getTopicId()); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Failed to process message queue after task callback', [ 'topic_id' => $event->getTopicId(), 'task_id' => $event->getTaskId(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); 
}
 
}
 
}
 
