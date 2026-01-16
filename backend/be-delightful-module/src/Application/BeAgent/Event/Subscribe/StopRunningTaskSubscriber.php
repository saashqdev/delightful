<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Delightful\BeDelightful\Application\SuperAgent\Service\AgentAppService;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\delete DataType;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Event\StopRunningTaskEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Model\TopicModel;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Exception\SandboxOperationException;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPtable ;
use Throwable;
/** * StopRunningTaskMessageSubscriber. */ #[Consumer( exchange: 'super_magic_stop_task', routingKey: 'super_magic_stop_task', queue: 'super_magic_stop_task', nums: 1 )]

class StopRunningTaskSubscriber extends ConsumerMessage 
{
 /** * @var AMQPtable |array QueueParameterfor Set Priority */ 
    protected AMQPtable |array $queueArguments = []; /** * @var null|array QoS Configurationfor Quantity */ protected ?array $qos = [ 'prefetch_count' => 1, // each time 1Message 'prefetch_size' => 0, 'global' => false, ]; /** * Function. */ 
    public function __construct( 
    private readonly TaskRepositoryInterface $taskRepository, 
    private readonly AgentAppService $agentAppService, 
    protected LockerInterface $locker, 
    private readonly StdoutLoggerInterface $logger ) 
{
 // Set QueuePriorityParameter $this->queueArguments['x-max-priority'] = ['I', 10]; // Set maximum priority as 10 
}
 /** * Message. * * @param mixed $data MessageData * @param AMQPMessage $message original MessageObject * @return Result process Result */ 
    public function consumeMessage($data, AMQPMessage $message): Result 
{
 try 
{
 // record ReceiveMessageContent $this->logger->info(sprintf( 'ReceiveStopTaskMessage: %s', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) )); // GetMessagePropertycheck seconds Timestamp $messageProperties = $message->get_properties(); $applicationHeaders = $messageProperties['application_headers'] ?? new AMQPtable ([]); $originalTimestampFromHeader = $applicationHeaders->getNativeData()['x-original-timestamp'] ?? null; $currentTimeForLog = time(); $actualOriginalTimestamp = null; if ($originalTimestampFromHeader !== null) 
{
 $actualOriginalTimestamp = (int) $originalTimestampFromHeader; $this->logger->info(sprintf( 'MessageAlready existsoriginal seconds Timestamp: %d (%s), event_id: %s', $actualOriginalTimestamp, date('Y-m-d H:i:s', $actualOriginalTimestamp), $data['event_id'] ?? 'N/A' )); 
}
 else 
{
 $actualOriginalTimestamp = $currentTimeForLog; $this->logger->warning(sprintf( 'Messagenot found x-original-timestamp Usingcurrent Timeas process original Timestamp: %d (%s). Event ID: %s', $actualOriginalTimestamp, date('Y-m-d H:i:s', $actualOriginalTimestamp), $data['event_id'] ?? 'N/A' )); 
}
 // Validate MessageFormat $this->validateMessageFormat($data); // CreateEventObject $event = StopRunningTaskEvent::fromArray($data); // directly process StopTaskLockAttopic Levelprocess $this->stopRunningTasks($event); return Result::ACK; 
}
 catch (BusinessException $e) 
{
 $this->logger->error(sprintf( 'process StopTaskMessageFailedException: %s, MessageContent: %s', $e->getMessage(), json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) )); return Result::ACK; 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'process StopTaskMessageFailedSystemException: %s, MessageContent: %s', $e->getMessage(), json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) )); return Result::ACK; 
}
 
}
 
    public function acquireLock(string $lockKey, string $lockowner , int $lockExpireSeconds): bool 
{
 return $this->locker->mutexLock($lockKey, $lockowner , $lockExpireSeconds); 
}
 /** * Validate MessageFormat. * * @param mixed $data MessageData * @throws BusinessException IfMessageFormatCorrectThrowException */ 
    private function validateMessageFormat($data): void 
{
 $requiredFields = [ 'event_id', 'data_type', 'data_id', 'user_id', 'organization_code', ]; foreach ($requiredFields as $field) 
{
 if (! isset($data[$field]) || (is_string($data[$field]) && empty($data[$field]))) 
{
 $this->logger->warning(sprintf( 'StopTaskMessageFormatCorrectmissing Field: %s, MessageContent: %s', $field, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) )); throw new BusinessException( Invalid message format: missing field 
{
$field
}
 ); 
}
 
}
 
}
 
    private function releaseLock(string $lockKey, string $lockowner ): bool 
{
 return $this->locker->release($lockKey, $lockowner ); 
}
 /** * StopRunningTask. * * @param StopRunningTaskEvent $event StopTaskEvent * @throws BusinessException|SandboxOperationException */ 
    private function stopRunningTasks(StopRunningTaskEvent $event): void 
{
 $this->logger->info(sprintf( 'Startprocess StopTaskRequestType: %s, ID: %d, user : %s, Organization: %s', $event->getDataType()->value, $event->getDataId(), $event->getuser Id(), $event->getOrganizationCode() )); try 
{
 // According toDataTypequery related RunningTask $runningTasks = $this->queryRunningTasksByDataType($event); if (empty($runningTasks)) 
{
 $this->logger->info(sprintf( 'not found need StopRunningTaskType: %s, ID: %d', $event->getDataType()->value, $event->getDataId() )); return; 
}
 $this->logger->info(sprintf( ' %d need StopRunningTaskType: %s, ID: %d', count($runningTasks), $event->getDataType()->value, $event->getDataId() )); // topic IDGroupTask $tasksByTopic = []; foreach ($runningTasks as $task) 
{
 $topicId = $task->getTopicId(); if (! isset($tasksByTopic[$topicId])) 
{
 $tasksByTopic[$topicId] = []; 
}
 $tasksByTopic[$topicId][] = $task; 
}
 // CreateDataObject $dataIsolation = new DataIsolation(); $dataIsolation->setcurrent user Id($event->getuser Id()); $dataIsolation->setcurrent OrganizationCode($event->getOrganizationCode()); // topic process Taskas each topic Lock $totalSuccessCount = 0; $totalFailureCount = 0; $skippedTopicCount = 0; foreach ($tasksByTopic as $topicId => $tasks) 
{
 // as current topic GetLock $lockKey = 'stop_running_tasks_topic_lock:' . $topicId; $lockowner = IdGenerator::getUniqueId32(); $lockExpireSeconds = 30; // Topic level lockSet 30 second timeout $lockAcquired = $this->acquireLock($lockKey, $lockowner , $lockExpireSeconds); if (! $lockAcquired) 
{
 $this->logger->info(sprintf( 'cannot Gettopic %d StopTaskLockSkiptopic %d Taskprocess event_id: %s', $topicId, count($tasks), $event->getEventId() )); ++$skippedTopicCount; continue; 
}
 $this->logger->info(sprintf( 'Gettopic %d StopTaskLockStartprocess %d Taskevent_id: %s', $topicId, count($tasks), $event->getEventId() )); try 
{
 $successCount = 0; $failureCount = 0; foreach ($tasks as $task) 
{
 try 
{
 $this->agentAppService->sendInterruptMessage( $dataIsolation, $task->getSandboxId(), $task->getTaskId(), $event->getReason() ); $this->logger->info(sprintf( 'SuccessSendInterruptMessagetopic ID: %d, TaskID: %s, Sandbox ID: %s', $topicId, $task->getTaskId(), $task->getSandboxId() )); ++$successCount; 
}
 catch (SandboxOperationException $e) 
{
 $this->logger->error(sprintf( 'SendInterruptMessageFailedtopic ID: %d, TaskID: %s, Sandbox ID: %s, Error: %s', $topicId, $task->getTaskId(), $task->getSandboxId(), $e->getMessage() )); ++$failureCount; 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'SendInterruptMessageoccurred Unknown errortopic ID: %d, TaskID: %s, Sandbox ID: %s, Error: %s', $topicId, $task->getTaskId(), $task->getSandboxId(), $e->getMessage() )); ++$failureCount; 
}
 
}
 $totalSuccessCount += $successCount; $totalFailureCount += $failureCount; $this->logger->info(sprintf( 'topic %d Taskprocess complete Success: %d, Failed: %d', $topicId, $successCount, $failureCount )); 
}
 finally 
{
 if ($this->releaseLock($lockKey, $lockowner )) 
{
 $this->logger->debug(sprintf( 'Releasetopic %d StopTaskLock', $topicId )); 
}
 else 
{
 $this->logger->error(sprintf( 'Releasetopic %d StopTaskLockFailedneed ', $topicId )); 
}
 
}
 
}
 $this->logger->info(sprintf( 'StopTaskprocess complete Type: %s, ID: %d, Success: %d, Failed: %d, Skiptopic : %d', $event->getDataType()->value, $event->getDataId(), $totalSuccessCount, $totalFailureCount, $skippedTopicCount )); 
}
 catch (Throwable $e) 
{
 $this->logger->error(sprintf( 'StopTaskprocess ing failedType: %s, ID: %d, Error: %s', $event->getDataType()->value, $event->getDataId(), $e->getMessage() )); throw $e; 
}
 
}
 /** * According toDataTypequery related RunningTask. * * @param StopRunningTaskEvent $event StopTaskEvent * @return array RunningTasklist */ 
    private function queryRunningTasksByDataType(StopRunningTaskEvent $event): array 
{
 $runningTasks = []; switch ($event->getDataType()) 
{
 case delete DataType::WORKSPACE: // query workspace AllRunningtopic Includedelete dtopic  $topicConditions = [ 'workspace_id' => $event->getDataId(), 'current_task_status' => TaskStatus::RUNNING->value, ]; $topicsResult = $this->queryTopicsIncludedelete d($topicConditions); $topics = $topicsResult['list'] ?? []; // query topic under RunningTask foreach ($topics as $topic) 
{
 $tasks = $this->getRunningTasksByTopicId($topic->getId()); $runningTasks = array_merge($runningTasks, $tasks); 
}
 break; case delete DataType::PROJECT: // query ItemAllRunningtopic Includedelete dtopic  $topicConditions = [ 'project_id' => $event->getDataId(), 'current_task_status' => TaskStatus::RUNNING->value, ]; $topicsResult = $this->queryTopicsIncludedelete d($topicConditions); $topics = $topicsResult['list'] ?? []; // query topic under RunningTask foreach ($topics as $topic) 
{
 $tasks = $this->getRunningTasksByTopicId($topic->getId()); $runningTasks = array_merge($runningTasks, $tasks); 
}
 break; case delete DataType::TOPIC: // directly query topic under RunningTask $runningTasks = $this->getRunningTasksByTopicId($event->getDataId()); break; default: $this->logger->warning(sprintf( 'UnknownDataType: %s', $event->getDataType()->value )); break; 
}
 return $runningTasks; 
}
 /** * query topic Includedelete dtopic . * Methodfor StopTaskneed query delete dtopic under RunningTask. * * @param array $conditions query Condition * @return array topic list */ 
    private function queryTopicsIncludedelete d(array $conditions): array 
{
 // Standard getTopicsByConditions Filterdelete dtopic  // need Using withTrashed() MethodGetIncludedelete dtopic /** @phpstan-ignore-next-line - TopicModel uses Softdelete s

trait which provides withTrashed() */ $query = TopicModel::query()->withTrashed(); // ApplyConditionFilter foreach ($conditions as $field => $value) 
{
 if (is_array($value)) 
{
 $query->whereIn($field, $value); 
}
 else 
{
 $query->where($field, $value); 
}
 
}
 // GetAlltopic Includedelete d $topics = $query->get(); $this->logger->info(sprintf( 'query topic Includedelete d %d topic query Condition%s', $topics->count(), json_encode($conditions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) )); // Convert toObject $list = []; foreach ($topics as $topic) 
{
 $list[] = new TopicEntity($topic->toArray()); 
}
 return [ 'list' => $list, 'total' => count($list), ]; 
}
 /** * According totopic IDGetRunningTask. * * @param int $topicId topic ID * @return array RunningTasklist */ 
    private function getRunningTasksByTopicId(int $topicId): array 
{
 $taskConditions = [ 'task_status' => [TaskStatus::RUNNING->value], ]; $tasksResult = $this->taskRepository->getTasksByTopicId($topicId, 1, 1000, $taskConditions); return $tasksResult['list'] ?? []; 
}
 
}
 
