<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\Start\V1;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\V1\StartNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

#[FlowNodeDefine(
    type: NodeType::Start->value,
    code: NodeType::Start->name,
    name: 'start',
    paramsConfig: StartNodeParamsConfig::class,
    version: 'v1',
    singleDebug: true,
    needInput: false,
    needOutput: false
)]
class StartNodeRunner extends AbstractStartNodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        // 触发methodfrom运行dataget，触发时只能haveone触发method
        $triggerType = $executionData->getTriggerType();
        if ($appointTriggerType = $frontResults['appoint_trigger_type'] ?? null) {
            if ($appointTriggerType instanceof TriggerType) {
                $triggerType = $appointTriggerType;
            }
        }

        /** @var StartNodeParamsConfig $paramsConfig */
        $paramsConfig = $this->node->getNodeParamsConfig();

        $triggerBranch = $paramsConfig->getBranchByTriggerType($triggerType);
        if (empty($triggerBranch)) {
            // ifnothave找to任何触发method，直接endthen好
            $vertexResult->clearChildren();
            return;
        }

        $this->logger->info(
            'start_node_runner',
            [
                'trigger_type' => $triggerType->name,
                'conversation_id' => $executionData->getOriginConversationId(),
                'message_id' => $executionData->getTriggerData()?->getMessageEntity()?->getDelightfulMessageId(),
            ]
        );
        $result = match ($triggerType) {
            TriggerType::ChatMessage => $this->chatMessage($vertexResult, $executionData, $triggerBranch),
            TriggerType::OpenChatWindow => $this->openChatWindow($vertexResult, $executionData, $triggerBranch),
            TriggerType::AddFriend => $this->addFriend($vertexResult, $executionData, $triggerBranch),
            TriggerType::LoopStart, TriggerType::ParamCall => $this->paramCall($vertexResult, $executionData, $triggerBranch),
            TriggerType::Routine => $this->routine($vertexResult, $executionData, $paramsConfig),
            default => ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.node.start.unsupported_trigger_type', ['trigger_type' => $triggerType->value]),
        };

        $vertexResult->setResult($result);
        $executionData->saveNodeContext($this->node->getNodeId(), $result);
    }
}
