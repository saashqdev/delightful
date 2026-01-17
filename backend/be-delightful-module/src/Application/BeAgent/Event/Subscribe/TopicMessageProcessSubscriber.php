<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Event\Subscribe;

use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use Delightful\BeDelightful\Application\BeAgent\Service\HandleAgentMessageAppService;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

/**
 * Topic message processing subscriber (based on database queue).
 * Consumes lightweight TopicMessageProcessEvent, processes messages sequentially from database.
 */
#[Consumer(
    exchange: 'be_delightful_topic_message_process',
    routingKey: 'be_delightful_topic_message_process',
    queue: 'be_delightful_topic_message_process',
    nums: 1
)]
class TopicMessageProcessSubscriber extends ConsumerMessage
{
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
        private readonly HandleAgentMessageAppService $handleAgentMessageAppService,
        protected LockerInterface $locker,
        private readonly StdoutLoggerInterface $logger,
    ) {
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
            // Log received lightweight event
            $this->logger->debug(sprintf(
                'Received topic message processing event: %s',
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));

            // Validate event format
            $this->validateEventFormat($data);

            // Get topic_id
            $topicId = (int) ($data['topic_id'] ?? 0);
            $taskId = (int) ($data['task_id'] ?? 0);
            if ($topicId <= 0) {
                $this->logger->warning('Invalid topic_id, skipping processing', [
                    'topic_id' => $topicId,
                    'event_data' => $data,
                ]);
                return Result::ACK;
            }

            // Try to acquire topic-level lock
            $lockKey = 'handle_topic_message_lock:' . $topicId;
            $lockOwner = IdGenerator::getUniqueId32();
            $lockExpireSeconds = 50; // Give batch processing more time

            $lockAcquired = $this->acquireLock($lockKey, $lockOwner, $lockExpireSeconds);

            if (! $lockAcquired) {
                $this->logger->info(sprintf(
                    'Cannot acquire lock for topic %d, another instance may be processing messages for this topic, directly ACK',
                    $topicId
                ));
                return Result::ACK; // Directly ACK, no retry
            }

            $this->logger->info(sprintf(
                'Acquired lock for topic %d, owner: %s, starting batch message processing',
                $topicId,
                $lockOwner
            ));

            try {
                // Call batch processing method
                $processedCount = $this->handleAgentMessageAppService->batchHandleAgentMessage($topicId, 0);

                $this->logger->info(sprintf(
                    'topic %d batch processing completed, processed message count: %d',
                    $topicId,
                    $processedCount
                ));

                return Result::ACK;
            } finally {
                if ($this->releaseLock($lockKey, $lockOwner)) {
                    $this->logger->info(sprintf(
                        'Released lock for topic %d, owner: %s',
                        $topicId,
                        $lockOwner
                    ));
                } else {
                    $this->logger->error(sprintf(
                        'Failed to release lock for topic %d, owner: %s, manual intervention may be required',
                        $topicId,
                        $lockOwner
                    ));
                }
            }
        } catch (BusinessException $e) {
            $this->logger->error(sprintf(
                'Failed to process topic message event, business exception: %s, event content: %s',
                $e->getMessage(),
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));
            return Result::ACK;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Failed to process topic message event, system exception: %s, event content: %s',
                $e->getMessage(),
                json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ));
            return Result::ACK;
        }
    }

    /**
     * Acquire distributed lock.
     */
    public function acquireLock(string $lockKey, string $lockOwner, int $lockExpireSeconds): bool
    {
        return $this->locker->spinLock($lockKey, $lockOwner, $lockExpireSeconds);
    }

    /**
     * Release distributed lock.
     */
    private function releaseLock(string $lockKey, string $lockOwner): bool
    {
        return $this->locker->release($lockKey, $lockOwner);
    }

    /**
     * Validate event format.
     *
     * @param mixed $data Event data
     * @throws BusinessException If event format is incorrect, throw exception
     */
    private function validateEventFormat($data): void
    {
        if (! is_array($data)) {
            throw new BusinessException('Event data format error: must be an array');
        }

        if (! isset($data['topic_id']) || ! is_numeric($data['topic_id'])) {
            throw new BusinessException('Event data format error: missing valid topic_id field');
        }
    }
}
