<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe;

use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Delightful\BeDelightful\Application\SuperAgent\Service\HandleAgentMessageAppService;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
/** * topic Messageprocess SubscriberBased onDatabaseQueue. * TopicMessageprocess EventFromDatabaseorder process Message. */ #[Consumer( exchange: 'super_magic_topic_message_process', routingKey: 'super_magic_topic_message_process', queue: 'super_magic_topic_message_process', nums: 1 )]

class TopicMessageprocess Subscriber extends ConsumerMessage 
{
 /** * @var null|array QoS Configurationfor Quantity */ protected ?array $qos = [ 'prefetch_count' => 1, // each time 1Message 'prefetch_size' => 0, 'global' => false, ]; /** * Function. */ 
    public function __construct( 
    private readonly HandleAgentMessageAppService $handleAgentMessageAppService, 
    protected LockerInterface $locker, 
    private readonly StdoutLoggerInterface $logger, ) 
{
 
}
 /** * Message. * * @param mixed $data MessageData * @param AMQPMessage $message original MessageObject * @return Result process Result */ 
    public function consumeMessage($data, AMQPMessage $message): Result 
{
 try 
{
 // record ReceiveEvent $this->logger->debug(sprintf( 'Receivetopic Messageprocess Event: %s', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) )); // Validate EventFormat $this->validateEventFormat($data); // Gettopic_id $topicId = (int) ($data['topic_id'] ?? 0); $taskId = (int) ($data['task_id'] ?? 0); if ($topicId <= 0) 
{
 $this->logger->warning('Invalidtopic_idSkipprocess ', [ 'topic_id' => $topicId, 'event_data' => $data, ]); return Result::ACK; 
}
 // try Gettopic LevelLock $lockKey = 'handle_topic_message_lock:' . $topicId; $lockowner = IdGenerator::getUniqueId32(); $lockExpireSeconds = 50; // Give batch processing more time $lockAcquired = $this->acquireLock($lockKey, $lockowner , $lockExpireSeconds); if (! $lockAcquired) 
{
 $this->logger->info(sprintf( 'cannot Gettopic %dLockHaveInstanceAtprocess topic Messagedirectly ACK', $topicId )); return Result::ACK; // directly ACKRetry 
}
 $this->logger->info(sprintf( 'Gettopic %dLockHave: %sStartBatchprocess Message', $topicId, $lockowner )); try 
{
 // call Batchprocess Method $processedCount = $this->handleAgentMessageAppService->batchHandleAgentMessage($topicId, 0); $this->logger->info(sprintf( 'topic %d Batchprocess complete process MessageQuantity: %d', $topicId, $processedCount )); return Result::ACK; 
}
 finally 
{
 if ($this->releaseLock($lockKey, $lockowner )) 
{
 $this->logger->info(sprintf( 'Releasetopic %dLockHave: %s', $topicId, $lockowner )); 
}
 else 
{
 $this->logger->error(sprintf( 'Releasetopic %dLockFailedHave: %sneed ', $topicId, $lockowner )); 
}
 
}
 
}
 catch (BusinessException $e) 
{
 $this->logger->error(sprintf( 'process topic MessageEventFailedException: %s, EventContent: %s', $e->getMessage(), json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) )); return Result::ACK; 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'process topic MessageEventFailedSystemException: %s, EventContent: %s', $e->getMessage(), json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) )); return Result::ACK; 
}
 
}
 /** * GetLock. */ 
    public function acquireLock(string $lockKey, string $lockowner , int $lockExpireSeconds): bool 
{
 return $this->locker->spinLock($lockKey, $lockowner , $lockExpireSeconds); 
}
 /** * ReleaseLock. */ 
    private function releaseLock(string $lockKey, string $lockowner ): bool 
{
 return $this->locker->release($lockKey, $lockowner ); 
}
 /** * Validate EventFormat. * * @param mixed $data EventData * @throws BusinessException IfEventFormatCorrectThrowException */ 
    private function validateEventFormat($data): void 
{
 if (! is_array($data)) 
{
 throw new BusinessException('EventDataFormat errorMust beArray'); 
}
 if (! isset($data['topic_id']) || ! is_numeric($data['topic_id'])) 
{
 throw new BusinessException('EventDataFormat errormissing Validtopic_idField'); 
}
 
}
 
}
 
