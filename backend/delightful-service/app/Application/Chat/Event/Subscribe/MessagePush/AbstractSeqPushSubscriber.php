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
 * messagepushmodepiece.
 * according togenerateseqbyanditprioritylevel,uselongconnectpushgiveuser.
 * eachseqmaybewant推giveuser1to几tencustomerclient.
 */
abstract class AbstractSeqPushSubscriber extends AbstractSeqConsumer
{
    protected AmqpTopicType $topic = AmqpTopicType::Seq;

    /**
     * 1.thisgroundopenhairo clocknotstart,avoidconsumetestenvironmentdata,causetestenvironmentuserreceivenottomessage
     * 2.ifthisgroundopenhairo clock想debug,pleasefromlineinthisgroundbuildfrontclientenvironment,moreexchangemqhost. orpersonapplyonedevenvironment,isolationmq.
     */
    public function isEnable(): bool
    {
        return config('amqp.enable_chat_seq');
    }

    /**
     * according tosequencecolumnnumberprioritylevel.actualo clocknotifyreceiveitemside. thismaybeneedpublishsubscribe.
     * @param SeqCreatedEvent $data
     */
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        $seqIds = (array) $data['seqIds'];
        if ($seqIds[0] === ControlMessageType::Ping->value) {
            return Result::ACK;
        }

        // notifyreceiveitemside
        $this->logger->info(sprintf('messagePush receivetomessage data:%s', Json::encode($data)));
        try {
            foreach ($seqIds as $seqId) {
                $seqId = (string) $seqId;
                // useredisdetectseqwhetheralreadyalreadytrymultipletime,ifexceedspass n time,thennotagainpush
                $seqRetryKey = sprintf('messagePush:seqRetry:%s', $seqId);
                $seqRetryCount = $this->redis->get($seqRetryKey);
                if ($seqRetryCount >= 3) {
                    $this->logger->error(sprintf('messagePush %s $seqRetryKey:%s $seqRetryCount:%d', $seqId, $seqRetryKey, $seqRetryCount));
                    return Result::ACK;
                }
                $this->addSeqRetryNumber($seqRetryKey);
                // recordseqtrypushcount,useatback続judgewhetherneedretry
                $this->delightfulSeqAppService->pushSeq($seqId);
                // noterror,notagainre-push
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
            // todo callmessagequalityguaranteemodepiece,ifisservicedevicestressbigcausefail,thenput intodelayretryqueue,andfingercountlevel延longretrytimebetweenseparator
            return Result::REQUEUE;
        }
        return Result::ACK;
    }
}
