<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Event\Subscribe;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Delightful\BeDelightful\Application\BeAgent\Service\AgentAppService;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\DeleteDataType;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\BeAgent\Event\StopRunningTaskEvent;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Model\TopicModel;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Exception\SandboxOperationException;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

/**
 * Stop running task message subscriber.
 */
#[Consumer(
    exchange: 'be_delightful_stop_task',
    routingKey: 'be_delightful_stop_task',
    queue: 'be_delightful_stop_task',
    nums: 1
)]
class StopRunningTaskSubscriber extends ConsumerMessage
{
    /**
     * @var AMQPTable|array Queue arguments for setting priority, etc.
     */
    protected AMQPTable|array $queueArguments = [];

    /**
     * @var null|array QoS configuration for controlling prefetch count, etc.
     */
    protected ?array $qos = [
        'prefetch_count' => 1, // Prefetch only 1 message at a time
        'prefetch_size' => 0,
        'global' => false,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly AgentAppService $agentAppService,
        protected LockerInterface $locker,
        private readonly StdoutLoggerInterface $logger
    ) {
        // Set queue priority parameters
        $this->queueArguments['x-max-priority'] = ['I', 10]; // Set max priority to 10
    }

    /**
     * Consume message.
     *
     * @param mixed $data Message data
     * @param AMQPMessage $message Raw message object
     * @return Result Processing result
     */
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        try {
            // Log received message content
            $this->logger->info(sprintf(
                'Received stop task message: %s',
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));

            // Get message properties and check second-level timestamp
            $messageProperties = $message->get_properties();
            $applicationHeaders = $messageProperties['application_headers'] ?? new AMQPTable([]);
            $originalTimestampFromHeader = $applicationHeaders->getNativeData()['x-original-timestamp'] ?? null;

            $currentTimeForLog = time();
            $actualOriginalTimestamp = null;

            if ($originalTimestampFromHeader !== null) {
                $actualOriginalTimestamp = (int) $originalTimestampFromHeader;
                $this->logger->info(sprintf(
                    'Message already has original second-level timestamp: %d (%s), event_id: %s',
                    $actualOriginalTimestamp,
                    date('Y-m-d H:i:s', $actualOriginalTimestamp),
                    $data['event_id'] ?? 'N/A'
                ));
            } else {
                $actualOriginalTimestamp = $currentTimeForLog;
                $this->logger->warning(sprintf(
                    'Message x-original-timestamp header not found, will use current time as reference for original timestamp in this processing: %d (%s). Event ID: %s',
                    $actualOriginalTimestamp,
                    date('Y-m-d H:i:s', $actualOriginalTimestamp),
                    $data['event_id'] ?? 'N/A'
                ));
            }

            // Validate message format
            $this->validateMessageFormat($data);

            // Create event object
            $event = StopRunningTaskEvent::fromArray($data);

            // Directly process stop task, lock granularity handled at topic level
            $this->stopRunningTasks($event);
            return Result::ACK;
        } catch (BusinessException $e) {
            $this->logger->error(sprintf(
                'Failed to process stop task message, business exception: %s, message content: %s',
                $e->getMessage(),
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));
            return Result::ACK;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to process stop task message, system exception: %s, message content: %s',
                $e->getMessage(),
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));
            return Result::ACK;
        }
    }

    public function acquireLock(string $lockKey, string $lockOwner, int $lockExpireSeconds): bool
    {
        return $this->locker->mutexLock($lockKey, $lockOwner, $lockExpireSeconds);
    }

    /**
     * Validate message format.
     *
     * @param mixed $data Message data
     * @throws BusinessException If message format is incorrect, throw exception
     */
    private function validateMessageFormat($data): void
    {
        $requiredFields = [
            'event_id',
            'data_type',
            'data_id',
            'user_id',
            'organization_code',
        ];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || (is_string($data[$field]) && empty($data[$field]))) {
                $this->logger->warning(sprintf(
                    'Stop task message format is incorrect, missing required field: %s, message content: %s',
                    $field,
                    json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                ));
                throw new BusinessException("Invalid message format: missing field {$field}");
            }
        }
    }

    private function releaseLock(string $lockKey, string $lockOwner): bool
    {
        return $this->locker->release($lockKey, $lockOwner);
    }

    /**
     * Stop running tasks.
     *
     * @param StopRunningTaskEvent $event Stop task event
     * @throws BusinessException|SandboxOperationException
     */
    private function stopRunningTasks(StopRunningTaskEvent $event): void
    {
        $this->logger->info(sprintf(
            'Starting to process stop task request, type: %s, ID: %d, user: %s, organization: %s',
            $event->getDataType()->value,
            $event->getDataId(),
            $event->getUserId(),
            $event->getOrganizationCode()
        ));

        try {
            // Query related running tasks by data type
            $runningTasks = $this->queryRunningTasksByDataType($event);

            if (empty($runningTasks)) {
                $this->logger->info(sprintf(
                    'No running tasks found to stop, type: %s, ID: %d',
                    $event->getDataType()->value,
                    $event->getDataId()
                ));
                return;
            }

            $this->logger->info(sprintf(
                'Found %d running tasks to stop, type: %s, ID: %d',
                count($runningTasks),
                $event->getDataType()->value,
                $event->getDataId()
            ));

            // Group tasks by topic ID
            $tasksByTopic = [];
            foreach ($runningTasks as $task) {
                $topicId = $task->getTopicId();
                if (! isset($tasksByTopic[$topicId])) {
                    $tasksByTopic[$topicId] = [];
                }
                $tasksByTopic[$topicId][] = $task;
            }

            // Create data isolation object
            $dataIsolation = new DataIsolation();
            $dataIsolation->setCurrentUserId($event->getUserId());
            $dataIsolation->setCurrentOrganizationCode($event->getOrganizationCode());

            // Process tasks by topic, lock each topic
            $totalSuccessCount = 0;
            $totalFailureCount = 0;
            $skippedTopicCount = 0;

            foreach ($tasksByTopic as $topicId => $tasks) {
                // Acquire lock for current topic
                $lockKey = 'stop_running_tasks_topic_lock:' . $topicId;
                $lockOwner = IdGenerator::getUniqueId32();
                $lockExpireSeconds = 30; // Topic-level lock, set 30 seconds timeout

                $lockAcquired = $this->acquireLock($lockKey, $lockOwner, $lockExpireSeconds);

                if (! $lockAcquired) {
                    $this->logger->info(sprintf(
                        'Cannot acquire stop task lock for topic %d, skipping processing of %d tasks for this topic, event_id: %s',
                        $topicId,
                        count($tasks),
                        $event->getEventId()
                    ));
                    ++$skippedTopicCount;
                    continue;
                }

                $this->logger->info(sprintf(
                    'Acquired stop task lock for topic %d, starting to process %d tasks, event_id: %s',
                    $topicId,
                    count($tasks),
                    $event->getEventId()
                ));

                try {
                    $successCount = 0;
                    $failureCount = 0;

                    foreach ($tasks as $task) {
                        try {
                            $this->agentAppService->sendInterruptMessage(
                                $dataIsolation,
                                $task->getSandboxId(),
                                $task->getTaskId(),
                                $event->getReason()
                            );

                            $this->logger->info(sprintf(
                                'Successfully sent interrupt message, topic ID: %d, task ID: %s, sandbox ID: %s',
                                $topicId,
                                $task->getTaskId(),
                                $task->getSandboxId()
                            ));

                            ++$successCount;
                        } catch (SandboxOperationException $e) {
                            $this->logger->error(sprintf(
                                'Failed to send interrupt message, topic ID: %d, task ID: %s, sandbox ID: %s, error: %s',
                                $topicId,
                                $task->getTaskId(),
                                $task->getSandboxId(),
                                $e->getMessage()
                            ));
                            ++$failureCount;
                        } catch (Throwable $e) {
                            $this->logger->error(sprintf(
                                'Unknown error occurred while sending interrupt message, topic ID: %d, task ID: %s, sandbox ID: %s, error: %s',
                                $topicId,
                                $task->getTaskId(),
                                $task->getSandboxId(),
                                $e->getMessage()
                            ));
                            ++$failureCount;
                        }
                    }

                    $totalSuccessCount += $successCount;
                    $totalFailureCount += $failureCount;

                    $this->logger->info(sprintf(
                        'Topic %d task processing completed, success: %d, failure: %d',
                        $topicId,
                        $successCount,
                        $failureCount
                    ));
                } finally {
                    if ($this->releaseLock($lockKey, $lockOwner)) {
                        $this->logger->debug(sprintf(
                            'Released stop task lock for topic %d',
                            $topicId
                        ));
                    } else {
                        $this->logger->error(sprintf(
                            'Failed to release stop task lock for topic %d, manual intervention may be required',
                            $topicId
                        ));
                    }
                }
            }

            $this->logger->info(sprintf(
                'Stop task processing completed, type: %s, ID: %d, total success: %d, total failure: %d, skipped topics: %d',
                $event->getDataType()->value,
                $event->getDataId(),
                $totalSuccessCount,
                $totalFailureCount,
                $skippedTopicCount
            ));
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Stop task processing failed, type: %s, ID: %d, error: %s',
                $event->getDataType()->value,
                $event->getDataId(),
                $e->getMessage()
            ));
            throw $e;
        }
    }

    /**
     * Query related running tasks by data type.
     *
     * @param StopRunningTaskEvent $event Stop task event
     * @return array List of running tasks
     */
    private function queryRunningTasksByDataType(StopRunningTaskEvent $event): array
    {
        $runningTasks = [];

        switch ($event->getDataType()) {
            case DeleteDataType::WORKSPACE:
                // Query all running topics in workspace (including deleted topics)
                $topicConditions = [
                    'workspace_id' => $event->getDataId(),
                    'current_task_status' => TaskStatus::RUNNING->value,
                ];
                $topicsResult = $this->queryTopicsIncludeDeleted($topicConditions);
                $topics = $topicsResult['list'] ?? [];

                // Query running tasks in these topics
                foreach ($topics as $topic) {
                    $tasks = $this->getRunningTasksByTopicId($topic->getId());
                    $runningTasks = array_merge($runningTasks, $tasks);
                }
                break;
            case DeleteDataType::PROJECT:
                // Query all running topics in project (including deleted topics)
                $topicConditions = [
                    'project_id' => $event->getDataId(),
                    'current_task_status' => TaskStatus::RUNNING->value,
                ];
                $topicsResult = $this->queryTopicsIncludeDeleted($topicConditions);
                $topics = $topicsResult['list'] ?? [];

                // Query running tasks in these topics
                foreach ($topics as $topic) {
                    $tasks = $this->getRunningTasksByTopicId($topic->getId());
                    $runningTasks = array_merge($runningTasks, $tasks);
                }
                break;
            case DeleteDataType::TOPIC:
                // Directly query running tasks in topic
                $runningTasks = $this->getRunningTasksByTopicId($event->getDataId());
                break;
            default:
                $this->logger->warning(sprintf(
                    'Unknown data type: %s',
                    $event->getDataType()->value
                ));
                break;
        }

        return $runningTasks;
    }

    /**
     * Query topics including deleted topics.
     * This method is used for stop task scenarios, needs to query running tasks in deleted topics.
     *
     * @param array $conditions Query conditions
     * @return array Topic list
     */
    private function queryTopicsIncludeDeleted(array $conditions): array
    {
        // Since the standard getTopicsByConditions filters out deleted topics,
        // we need to use the withTrashed() method to get topics including deleted ones
        /** @phpstan-ignore-next-line - TopicModel uses SoftDeletes trait which provides withTrashed() */
        $query = TopicModel::query()->withTrashed();

        // Apply condition filters
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }

        // Get all topics, including deleted ones
        $topics = $query->get();

        $this->logger->info(sprintf(
            'Query topics (including deleted): found %d topics, query conditions: %s',
            $topics->count(),
            json_encode($conditions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ));

        // Convert to entity objects
        $list = [];
        foreach ($topics as $topic) {
            $list[] = new TopicEntity($topic->toArray());
        }

        return [
            'list' => $list,
            'total' => count($list),
        ];
    }

    /**
     * Get running tasks by topic ID.
     *
     * @param int $topicId Topic ID
     * @return array List of running tasks
     */
    private function getRunningTasksByTopicId(int $topicId): array
    {
        $taskConditions = [
            'task_status' => [TaskStatus::RUNNING->value],
        ];

        $tasksResult = $this->taskRepository->getTasksByTopicId($topicId, 1, 1000, $taskConditions);
        return $tasksResult['list'] ?? [];
    }
}
