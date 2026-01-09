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
        # 传入的参数:
        // 1. $userAccountEntity 包含真名,手机号等有安全风险,应该需要auth授权的information
        // 2. $userEntity user详情,包含userid,user昵称,user头像等information
        // 3. $seqEntity conversation窗口id,引用的message_id,messagetype(聊天message/打开了conversation窗口)
        // 4. $messageEntity 保存有messagetype,message的具体content,发件人id,发送时间
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
