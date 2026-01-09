<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Event\Subscribe\MessagePush;

use App\Application\Chat\Event\Subscribe\AbstractSeqConsumer;
use App\Domain\Chat\Entity\ValueObject\AmqpTopicType;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Event\Seq\SeqCreatedEvent;
use Hyperf\Amqp\Result;
use Hyperf\Codec\Json;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

/**
 * messagepush模块.
 * according togenerate的seq以及它的优先级,用长连接push给user.
 * 每个seq可能要推给user的1到几十个客户端.
 */
abstract class AbstractSeqPushSubscriber extends AbstractSeqConsumer
{
    protected AmqpTopicType $topic = AmqpTopicType::Seq;

    /**
     * 1.本地开发时不启动,避免消费了test环境的数据,导致test环境的user收不到message
     * 2.如果本地开发时想debug,请自行在本地搭建前端环境,更换mq的host. 或者申请一个dev环境,隔离mq.
     */
    public function isEnable(): bool
    {
        return config('amqp.enable_chat_seq');
    }

    /**
     * according to序列号优先级.实时notify收件方. 这可能needpublishsubscribe.
     * @param SeqCreatedEvent $data
     */
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        $seqIds = (array) $data['seqIds'];
        if ($seqIds[0] === ControlMessageType::Ping->value) {
            return Result::ACK;
        }

        // notify收件方
        $this->logger->info(sprintf('messagePush 收到message data:%s', Json::encode($data)));
        try {
            foreach ($seqIds as $seqId) {
                $seqId = (string) $seqId;
                // 用redis检测seq是否已经尝试多次,如果超过 n 次,则不再push
                $seqRetryKey = sprintf('messagePush:seqRetry:%s', $seqId);
                $seqRetryCount = $this->redis->get($seqRetryKey);
                if ($seqRetryCount >= 3) {
                    $this->logger->error(sprintf('messagePush %s $seqRetryKey:%s $seqRetryCount:%d', $seqId, $seqRetryKey, $seqRetryCount));
                    return Result::ACK;
                }
                $this->addSeqRetryNumber($seqRetryKey);
                // recordseq尝试push的次数,用于后续判断是否needretry
                $this->delightfulSeqAppService->pushSeq($seqId);
                // 未报错,不再重推
                $this->setSeqCanNotRetry($seqRetryKey);
            }
        } catch (Throwable $exception) {
            $this->logger->error(sprintf(
                'messagePush: %s file:%s line:%d trace: %s',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
                $exception->getTraceAsString()
            ));
            // todo callmessage质量保证模块,如果是service器stress大导致的fail,则放入delayretryqueue,并指数级延长retrytime间隔
            return Result::REQUEUE;
        }
        return Result::ACK;
    }
}
