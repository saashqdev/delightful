<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\Sub;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\ExecutionData\TriggerData;
use App\Application\Flow\ExecuteManager\DelightfulFlowExecutor;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunner;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Sub\SubNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use DateTime;
use Throwable;

#[FlowNodeDefine(type: NodeType::Sub->value, code: NodeType::Sub->name, name: '子process', paramsConfig: SubNodeParamsConfig::class, version: 'v0', singleDebug: true, needInput: true, needOutput: true)]
class SubNodeRunner extends NodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        $subFlowId = $this->node->getParams()['sub_flow_id'] ?? '';

        // 运lineo clock才get子processdata,thiswithinshouldin运lineo clockthenloadgood,thiswithinforconvenient先thishow to write
        $subFlow = $this->delightfulFlowDomainService->getByCode($executionData->getDataIsolation(), $subFlowId);
        if (! $subFlow || $subFlow->getType() !== Type::Sub) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.node.sub.flow_not_found', ['flow_code' => $subFlowId]);
        }

        // getstartsectionpoint,endsectionpoint
        if (! $subFlow->getStartNode()) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.node.sub.start_node_not_found', ['flow_code' => $subFlowId]);
        }
        if (! $subFlow->getEndNode()) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.node.sub.end_node_not_found', ['flow_code' => $subFlowId]);
        }

        $inputResult = $this->node->getInput()?->getForm()?->getForm()?->getKeyValue($executionData->getExpressionFieldData()) ?? [];
        $vertexResult->setInput($inputResult);

        $triggerData = new TriggerData(
            triggerTime: new DateTime(),
            userInfo: $executionData->getTriggerData()->getUserInfo(),
            messageInfo: $executionData->getTriggerData()->getMessageInfo(),
            params: $inputResult,
            globalVariable: $subFlow->getGlobalVariable(),
            triggerDataUserExtInfo: $executionData->getTriggerData()?->getUserExtInfo()
        );

        try {
            $subExecutionData = new ExecutionData(
                flowDataIsolation: $executionData->getDataIsolation(),
                operator: $executionData->getOperator(),
                triggerType: TriggerType::ParamCall,
                triggerData: $triggerData,
                id: $executionData->getId(),
                conversationId: $executionData->getConversationId(),
                executionType: $executionData->getExecutionType(),
            );
            $subExecutionData->extends($executionData);
            $subExecutor = new DelightfulFlowExecutor($subFlow, $subExecutionData);
            $subExecutor->execute();
        } catch (Throwable $throwable) {
            ExceptionBuilder::throw(
                FlowErrorCode::ExecuteFailed,
                'flow.node.sub.execute_failed',
                ['flow_name' => $subFlow->getName(), 'error' => $throwable->getMessage()]
            );
        }
        // sectionpointinside部exceptionin node  debug informationmiddlerecord
        foreach ($subFlow->getNodes() as $node) {
            if ($node->getNodeDebugResult() && ! $node->getNodeDebugResult()->isSuccess()) {
                ExceptionBuilder::throw(
                    FlowErrorCode::ExecuteFailed,
                    'flow.node.sub.execute_failed',
                    ['name' => $subFlow->getName(), 'error' => $node->getNodeDebugResult()->getErrorMessage()]
                );
            }
        }
        $result = $subExecutionData->getNodeContext($subFlow->getEndNode()->getNodeId());

        $vertexResult->setResult($result);
        $executionData->saveNodeContext($this->node->getNodeId(), $result);
    }
}
