<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Event\Subscribe;

use App\Application\Chat\Service\DelightfulChatMessageAppService;
use App\Application\Chat\Service\DelightfulControlMessageAppService;
use App\Application\Chat\Service\DelightfulSeqAppService;
use App\Domain\Chat\Entity\ValueObject\AmqpTopicType;
use App\Domain\Chat\Entity\ValueObject\MessagePriority;
use App\Domain\Chat\Repository\Facade\DelightfulChatSeqRepositoryInterface;
use App\Domain\Chat\Service\DelightfulSeqDomainService;
use App\Domain\Contact\Repository\Persistence\DelightfulUserRepository;
use App\Infrastructure\Core\Traits\ChatAmqpTrait;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Hyperf\Amqp\Builder\QueueBuilder;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use RedisException;

abstract class AbstractSeqConsumer extends ConsumerMessage
{
    use ChatAmqpTrait;

    // 子类need指明 topic type
    protected AmqpTopicType $topic;

    protected LoggerInterface $logger;

    /**
     * settingqueue优先级parameter.
     */
    protected AMQPTable|array $arguments = [
        'x-ha-policy' => ['S', 'all'], // 将queue镜像to所have节点,hyperf defaultconfiguration
    ];

    protected MessagePriority $priority;

    public function __construct(
        protected Redis $redis,
        protected DelightfulSeqAppService $delightfulSeqAppService,
        protected DelightfulChatSeqRepositoryInterface $delightfulChatSeqRepository,
        protected DelightfulChatMessageAppService $delightfulChatMessageAppService,
        protected DelightfulControlMessageAppService $delightfulControlMessageAppService,
        protected DelightfulSeqDomainService $delightfulSeqDomainService,
        protected DelightfulUserRepository $delightfulUserRepository,
    ) {
        // setting列队优先级
        $this->arguments['x-max-priority'] = ['I', $this->priority->value];
        $this->exchange = $this->getExchangeName($this->topic);
        $this->routingKey = $this->getRoutingKeyName($this->topic, $this->priority);
        $this->queue = sprintf('%s.%s.queue', $this->exchange, $this->priority->name);
        $this->logger = ApplicationContext::getContainer()->get(LoggerFactory::class)->get(get_class($this));
    }

    /**
     * inheritbyimplementsettingqueue相关parameter.
     */
    public function getQueueBuilder(): QueueBuilder
    {
        return (new QueueBuilder())->setQueue($this->getQueue())->setArguments($this->arguments);
    }

    protected function setSeqCanNotRetry(string $retryCacheKey)
    {
        // notagain重推
        try {
            $this->redis->set($retryCacheKey, 3);
            $this->redis->expire($retryCacheKey, 3600);
        } catch (RedisException) {
        }
    }

    protected function addSeqRetryNumber(string $retryCacheKey)
    {
        // notagain重推
        try {
            $this->redis->incr($retryCacheKey);
            $this->redis->expire($retryCacheKey, 3600);
        } catch (RedisException) {
        }
    }

    protected function setRequestId(string $appMsgId): void
    {
        // use app_msg_id 做 request_id
        $requestId = empty($appMsgId) ? IdGenerator::getSnowId() : $appMsgId;
        CoContext::setRequestId((string) $requestId);
    }
}
