<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Event\Subscribe\MessageDispatch;

use App\Application\Chat\Event\Subscribe\AbstractSeqConsumer;
use App\Domain\Chat\Entity\ValueObject\AmqpTopicType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ChatMessageType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Event\Seq\SeqCreatedEvent;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\ErrorCode\ChatErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Locker\LockerInterface;
use Hyperf\Amqp\Result;
use Hyperf\Codec\Json;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

use function Hyperf\Support\retry;

/**
 * message分发模块.
 * processdifferent优先级message的消费者,用于写收件方的seq.
 */
abstract class AbstractMessageDispatchSubscriber extends AbstractSeqConsumer
{
    protected AmqpTopicType $topic = AmqpTopicType::Message;

    /**
     * 1.本地开发时不启动,避免消费了test环境的data,导致test环境的user收不到message
     * 2.如果本地开发时想debug,请自行在本地搭建前端环境,更换mq的host. 或者申请一个dev环境,隔离mq.
     */
    public function isEnable(): bool
    {
        return config('amqp.enable_chat_message');
    }

    /**
     * according tomessage优先级.将收件方的messagegenerate序列号.
     * @param SeqCreatedEvent $data
     */
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        $seqIds = (array) $data['seqIds'];
        if ($seqIds[0] === ControlMessageType::Ping->value) {
            return Result::ACK;
        }
        $conversationId = $data['conversationId'] ?? null;
        // generate收件方的seq
        $this->logger->info(sprintf('messageDispatch 收到message data:%s', Json::encode($data)));
        $lock = di(LockerInterface::class);
        try {
            if ($conversationId) {
                $lockKey = sprintf('messageDispatch:lock:%s', $conversationId);
                $owner = uniqid('', true);
                $lock->spinLock($lockKey, $owner);
            }
            foreach ($seqIds as $seqId) {
                $seqId = (string) $seqId;
                // 用redis检测seq是否已经尝试多次,如果超过 n 次,则不再push
                $seqRetryKey = sprintf('messageDispatch:seqRetry:%s', $seqId);
                $seqRetryCount = $this->redis->get($seqRetryKey);
                if ($seqRetryCount >= 3) {
                    $this->logger->error(sprintf('messageDispatch  $seqRetryKey:%s $seqRetryCount:%d', $seqRetryKey, $seqRetryCount));
                    return Result::ACK;
                }
                $this->addSeqRetryNumber($seqRetryKey);
                $userSeqEntity = null;
                // 查seq,faildelay后retry3次
                retry(3, function () use ($seqId, &$userSeqEntity) {
                    $userSeqEntity = $this->delightfulChatSeqRepository->getSeqByMessageId($seqId);
                    if ($userSeqEntity === null) {
                        // 可能是transaction还未submit,mq已经消费,delayretry
                        ExceptionBuilder::throw(ChatErrorCode::SEQ_NOT_FOUND);
                    }
                }, 100);
                // send方的seq
                if ($userSeqEntity === null) {
                    $this->logger->error('messageDispatch seq not found:{seq_id} ', ['seq_id' => $seqId]);
                    $this->setSeqCanNotRetry($seqRetryKey);
                }
                $this->setRequestId($userSeqEntity->getAppMessageId());
                $this->logger->info(sprintf('messageDispatch 开始分发message seq:%s seqEntity:%s ', $seqId, Json::encode($userSeqEntity->toArray())));
                // 如果是控制message,check是否是need分发的控制message
                if ($userSeqEntity->getSeqType() instanceof ControlMessageType) {
                    $this->delightfulControlMessageAppService->dispatchMQControlMessage($userSeqEntity);
                    $this->setSeqCanNotRetry($seqRetryKey);
                    if ($userSeqEntity->canTriggerFlow()) {
                        $dataIsolation = new DataIsolation();
                        $dataIsolation->setCurrentOrganizationCode($userSeqEntity->getOrganizationCode());
                        $userEntity = $this->delightfulUserRepository->getUserByDelightfulId($dataIsolation, $userSeqEntity->getObjectId());
                        if ($userEntity === null) {
                            $this->logger->error('messageDispatch user not found: seqId:' . $seqId);
                            return Result::ACK;
                        }
                        $this->delightfulSeqDomainService->pushControlSeq($userSeqEntity, $userEntity);
                    }
                }
                if ($userSeqEntity->getSeqType() instanceof ChatMessageType) {
                    // chatmessage分发
                    $this->delightfulChatMessageAppService->asyncHandlerChatMessage($userSeqEntity);
                }
                // seq processsuccess
                $this->setSeqCanNotRetry($seqRetryKey);
            }
        } catch (Throwable $exception) {
            $this->logger->error(sprintf(
                'messageDispatch error: %s file:%s line:%d trace: %s',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            ));
            // todo callmessage质量保证模块,如果是service器stress大导致的fail,则放入delayretryqueue,并指数级延长retrytime间隔
            return Result::REQUEUE;
        } finally {
            if (isset($lockKey, $owner)) {
                $lock->release($lockKey, $owner);
            }
        }
        return Result::ACK;
    }
}
