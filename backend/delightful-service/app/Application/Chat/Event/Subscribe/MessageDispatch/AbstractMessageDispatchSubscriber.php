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
 * messageminutehair模piece.
 * processdifferentprioritylevelmessageconsumer,useat写收item方seq.
 */
abstract class AbstractMessageDispatchSubscriber extends AbstractSeqConsumer
{
    protected AmqpTopicType $topic = AmqpTopicType::Message;

    /**
     * 1.本groundopenhairo clocknotstart,avoidconsumetestenvironmentdata,causetestenvironmentuser收nottomessage
     * 2.if本groundopenhairo clock想debug,请fromlinein本ground搭建front端environment,more换mqhost. or者applyonedevenvironment,isolationmq.
     */
    public function isEnable(): bool
    {
        return config('amqp.enable_chat_message');
    }

    /**
     * according tomessageprioritylevel.will收item方messagegenerate序columnnumber.
     * @param SeqCreatedEvent $data
     */
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        $seqIds = (array) $data['seqIds'];
        if ($seqIds[0] === ControlMessageType::Ping->value) {
            return Result::ACK;
        }
        $conversationId = $data['conversationId'] ?? null;
        // generate收item方seq
        $this->logger->info(sprintf('messageDispatch 收tomessage data:%s', Json::encode($data)));
        $lock = di(LockerInterface::class);
        try {
            if ($conversationId) {
                $lockKey = sprintf('messageDispatch:lock:%s', $conversationId);
                $owner = uniqid('', true);
                $lock->spinLock($lockKey, $owner);
            }
            foreach ($seqIds as $seqId) {
                $seqId = (string) $seqId;
                // useredisdetectseqwhetheralready经try多time,if超pass n time,thennotagainpush
                $seqRetryKey = sprintf('messageDispatch:seqRetry:%s', $seqId);
                $seqRetryCount = $this->redis->get($seqRetryKey);
                if ($seqRetryCount >= 3) {
                    $this->logger->error(sprintf('messageDispatch  $seqRetryKey:%s $seqRetryCount:%d', $seqRetryKey, $seqRetryCount));
                    return Result::ACK;
                }
                $this->addSeqRetryNumber($seqRetryKey);
                $userSeqEntity = null;
                // 查seq,faildelaybackretry3time
                retry(3, function () use ($seqId, &$userSeqEntity) {
                    $userSeqEntity = $this->delightfulChatSeqRepository->getSeqByMessageId($seqId);
                    if ($userSeqEntity === null) {
                        // maybeistransactionalsonotsubmit,mqalready经consume,delayretry
                        ExceptionBuilder::throw(ChatErrorCode::SEQ_NOT_FOUND);
                    }
                }, 100);
                // send方seq
                if ($userSeqEntity === null) {
                    $this->logger->error('messageDispatch seq not found:{seq_id} ', ['seq_id' => $seqId]);
                    $this->setSeqCanNotRetry($seqRetryKey);
                }
                $this->setRequestId($userSeqEntity->getAppMessageId());
                $this->logger->info(sprintf('messageDispatch startminutehairmessage seq:%s seqEntity:%s ', $seqId, Json::encode($userSeqEntity->toArray())));
                // ifiscontrolmessage,checkwhetherisneedminutehaircontrolmessage
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
                    // chatmessageminutehair
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
            // todo callmessagequalityguarantee模piece,ifisservice器stressbigcausefail,thenput intodelayretryqueue,andfinger数level延longretrytimebetween隔
            return Result::REQUEUE;
        } finally {
            if (isset($lockKey, $owner)) {
                $lock->release($lockKey, $owner);
            }
        }
        return Result::ACK;
    }
}
