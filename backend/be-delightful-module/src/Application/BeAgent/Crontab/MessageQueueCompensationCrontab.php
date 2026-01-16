<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Crontab;

use Delightful\BeDelightful\Application\SuperAgent\Service\MessageQueueCompensationAppService;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\Annotation\Crontab;
use Throwable;
/** * Message Queue Compensation Crontab. * MessageQueuecompensation ScheduledTask - process MessageQueuecompensation . */ #[Crontab( rule: '*/30 * * * * *', // execute every 30 seconds name: 'MessageQueueCompensationCrontab', singleton: true, // Singleton mode to prevent duplicate execution mutexExpires: 60, // Mutex lock expires in 60 seconds onOneServer: true, // execute on only one server callback: 'execute', memo: 'Message queue compensation scheduled task for handling missed message queues' )] readonly

class MessageQueueCompensationCrontab 
{
 
    public function __construct( 
    private MessageQueueCompensationAppService $messageQueueCompensationAppService, 
    private StdoutLoggerInterface $logger, ) 
{
 
}
 /** * Main execution method. * Primaryexecute Method. */ 
    public function execute(): void 
{
 // check if compensation is enabled in configuration $enabled = config('super-magic.user_message_queue.enabled', true); if (! $enabled) 
{
 return; 
}
 $startTime = microtime(true); $this->logger->info('Message queue compensation task started'); try 
{
 // execute compensation through application service $stats = $this->messageQueueCompensationAppService->executeCompensation(); $this->logger->info('Message queue compensation task completed', $stats); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Message queue compensation task failed', [ 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), ]); 
}
 
}
 
}
 
