<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Chat\Service;

use App\Application\Chat\Event\Subscribe\Agent\Factory\AgentFactory;
use App\Domain\Chat\Entity\ValueObject\MessageType\ControlMessageType;
use App\Domain\Chat\Event\Agent\AgentExecuteInterface;
use App\Domain\Chat\Event\Agent\UserCallAgentEvent;
use App\Domain\Chat\Service\DelightfulConversationDomainService;

class DelightfulAgentEventAppService implements AgentExecuteInterface
{
    public function __construct(
        protected readonly DelightfulConversationDomainService $delightfulConversationDomainService,
    ) {
    }

    public function agentExecEvent(UserCallAgentEvent $userCallAgentEvent)
    {
        $seqEntity = $userCallAgentEvent->seqEntity;
        $agentAccountEntity = $userCallAgentEvent->agentAccountEntity;

        // process开始execute前,触发开始inputevent
        if ($seqEntity->canTriggerFlow()) {
            $this->delightfulConversationDomainService->agentOperateConversationStatusV2(
                ControlMessageType::StartConversationInput,
                $seqEntity->getConversationId(),
                $seqEntity->getExtra()?->getTopicId()
            );
        }

        // executeprocess
        AgentFactory::make($agentAccountEntity->getAiCode())->execute($userCallAgentEvent);

        // processexecute结束，push结束inputevent
        // ai准备开始发message了,结束inputstatus
        if ($seqEntity->canTriggerFlow()) {
            $this->delightfulConversationDomainService->agentOperateConversationStatusV2(
                ControlMessageType::EndConversationInput,
                $seqEntity->getConversationId(),
                $seqEntity->getExtra()?->getTopicId()
            );
        }
    }
}
