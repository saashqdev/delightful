<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Event\Subscribe\Agent\Agents;

use App\Application\Flow\Service\DelightfulFlowExecuteAppService;
use App\Domain\Chat\Event\Agent\UserCallAgentEvent;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

use function di;

class DefaultAgent extends AbstractAgent
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerFactory $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    public function execute(UserCallAgentEvent $event): void
    {
        $seqEntity = $event->seqEntity;
        $messageEntity = $event->messageEntity;
        $agentAccountEntity = $event->agentAccountEntity;
        $agentUserEntity = $event->agentUserEntity;
        $senderUserEntity = $event->senderUserEntity;
        $senderAccountEntity = $event->senderAccountEntity;
        $senderExtraDTO = $event->senderExtraDTO;
        $logMessageData = $messageEntity?->toArray();
        unset($logMessageData['message_content']);
        $this->logger->info('ImChatMessageStart', [
            'seq' => $seqEntity->toArray(),
            'message' => $logMessageData,
        ]);
        // get触发type
        $triggerType = TriggerType::fromSeqType($seqEntity->getSeqType());
        # 传入的parameter:
        // 1. $userAccountEntity containtrue名,手机号etchavesecurity风险,shouldneedauthauthorization的information
        // 2. $userEntity userdetail,containuserid,user昵称,useravataretcinformation
        // 3. $seqEntity conversation窗口id,quote的message_id,messagetype(chatmessage/open了conversation窗口)
        // 4. $messageEntity savehavemessagetype,message的specificcontent,发件人id,sendtime
        $this->getDelightfulFlowExecuteAppService()->imChat(
            $agentAccountEntity->getAiCode(),
            $triggerType,
            [
                'agent_account' => $agentAccountEntity,
                'agent' => $agentUserEntity,
                'sender' => $senderUserEntity,
                'sender_account' => $senderAccountEntity,
                'seq' => $seqEntity,
                'message' => $messageEntity,
                'sender_extra' => $senderExtraDTO,
            ],
        );
    }

    private function getDelightfulFlowExecuteAppService(): DelightfulFlowExecuteAppService
    {
        return di(DelightfulFlowExecuteAppService::class);
    }
}
