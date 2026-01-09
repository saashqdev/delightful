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
 * messagepush模piece.
 * according togenerateseqbyanditprioritylevel,uselongconnectpushgiveuser.
 * eachseqmaybewant推giveuser1to几tencustomer端.
 */
abstract class AbstractSeqPushSubscriber extends AbstractSeqConsumer
{
    protected AmqpTopicType $topic = AmqpTopicType::Seq;

    /**
     * 1.本groundopenhairo clocknotstart,avoidconsumetestenvironmentdata,causetestenvironmentuser收nottomessage
     * 2.if本groundopenhairo clock想debug,请fromlinein本ground搭建front端environment,more换mqhost. or者applyonedevenvironment,isolationmq.
     */
    public function isEnable(): bool
    {
        return config('amqp.enable_chat_seq');
    }

    /**
     * according to序columnnumberprioritylevel.实o clocknotify收item方. thismaybeneedpublishsubscribe.
     * @param SeqCreatedEvent $data
     */
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        $seqIds = (array) $data['seqIds'];
        if ($seqIds[0] === ControlMessageType::Ping->value) {
            return Result::ACK;
        }

        // notify收item方
        $this->logger->info(sprintf('messagePush 收tomessage data:%s', Json::encode($data)));
        try {
            foreach ($seqIds as $seqId) {
                $seqId = (string) $seqId;
                // useredisdetectseqwhetheralready经try多time,if超pass n time,thennotagainpush
                $seqRetryKey = sprintf('messagePush:seqRetry:%s', $seqId);
                $seqRetryCount = $this->redis->get($seqRetryKey);
                if ($seqRetryCount >= 3) {
                    $this->logger->error(sprintf('messagePush %s $seqRetryKey:%s $seqRetryCount:%d', $seqId, $seqRetryKey, $seqRetryCount));
                    return Result::ACK;
                }
                $this->addSeqRetryNumber($seqRetryKey);
                // recordseqtrypushcount,useatback续judgewhetherneedretry
                $this->delightfulSeqAppService->pushSeq($seqId);
                // noterror,notagain重推
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
            // todo callmessagequalityguarantee模piece,ifisservice器stressbigcausefail,thenput intodelayretryqueue,andfinger数level延longretrytimebetween隔
            return Result::REQUEUE;
        }
        return Result::ACK;
    }
}
