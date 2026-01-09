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
        // gettouchhairtype
        $triggerType = TriggerType::fromSeqType($seqEntity->getSeqType());
        # pass inparameter:
        // 1. $userAccountEntity containtruename,handmachinenumberetchavesecurityrisk,shouldneedauthauthorizationinformation
        // 2. $userEntity userdetail,containuserid,usernickname,useravataretcinformation
        // 3. $seqEntity conversationwindowid,quotemessage_id,messagetype(chatmessage/openconversationwindow)
        // 4. $messageEntity savehavemessagetype,messagespecificcontent,hairitempersonid,sendtime
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
