<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\Loop;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionFlowCollector;
use App\Application\Flow\ExecuteManager\DelightfulFlowExecutor;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunner;
use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Loop\LoopMainNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Loop\LoopType;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\FlowExprEngine\ComponentFactory;
use Throwable;

#[FlowNodeDefine(
    type: NodeType::LoopMain->value,
    code: NodeType::LoopMain->name,
    name: '循环 / 主循环',
    paramsConfig: LoopMainNodeParamsConfig::class,
    version: 'v0',
    singleDebug: false,
    needInput: false,
    needOutput: false,
)]
class LoopMainNodeRunner extends NodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        $bodyId = $this->node->getMeta()['relation_id'] ?? '';
        if (empty($bodyId)) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.node.loop.relation_id_empty');
        }
        $delightfulFlow = ExecutionFlowCollector::getOrCreate($executionData->getUniqueId(), $executionData->getDelightfulFlowEntity());

        $loopFlow = $this->createLoopFlow($bodyId, $delightfulFlow);
        if (! $loopFlow) {
            return;
        }

        $breakVariableKey = "#{$bodyId}_break";
        // 采usevariable来initialize跳出循环configuration
        $executionData->variableSave($breakVariableKey, false);

        $params = $this->node->getParams();
        $type = LoopType::tryFrom($params['type'] ?? '');

        switch ($type) {
            case LoopType::Count:
                $countComponent = ComponentFactory::fastCreate($params['count'] ?? []);
                if (! $countComponent?->isValue()) {
                    ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.component.format_error', ['label' => 'count']);
                }
                $count = $countComponent->getValue()->getResult($executionData->getExpressionFieldData()) ?? 0;
                if (! is_numeric($count) || $count <= 0) {
                    ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.node.loop.count_format_error');
                }
                for ($i = 0; $i < $count; ++$i) {
                    $this->runLoopFlow($loopFlow, $executionData);
                    if ($executionData->variableGet($breakVariableKey, false)) {
                        break;
                    }
                }
                break;
            case LoopType::Array:
                $arrayComponent = ComponentFactory::fastCreate($params['array'] ?? []);
                if (! $arrayComponent?->isValue()) {
                    ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.component.format_error', ['label' => 'array']);
                }
                $array = $arrayComponent->getValue()->getResult($executionData->getExpressionFieldData()) ?? [];
                if (! is_array($array)) {
                    ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.node.loop.array_format_error');
                }
                foreach ($array as $index => $item) {
                    $executionData->saveNodeContext($this->node->getNodeId(), [
                        'item' => $item,
                        'index' => $index,
                    ]);
                    $this->runLoopFlow($loopFlow, $executionData);
                    if ($executionData->variableGet($breakVariableKey, false)) {
                        break;
                    }
                }
                break;
            case LoopType::Condition:
                $conditionComponent = ComponentFactory::fastCreate($params['condition'] ?? []);
                if (! $conditionComponent?->isCondition()) {
                    ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.component.format_error', ['label' => 'condition']);
                }
                // initialitemitem
                $condition = $conditionComponent->getCondition()->getResult($executionData->getExpressionFieldData()) ?? [];

                $maxLoopCountComponent = ComponentFactory::fastCreate($params['max_loop_count'] ?? []);
                if (! $maxLoopCountComponent?->isValue()) {
                    ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.component.format_error', ['label' => 'max_loop_count']);
                }
                $maxLoopCount = $maxLoopCountComponent->getValue()->getResult($executionData->getExpressionFieldData()) ?? 0;
                if (! is_numeric($maxLoopCount) || $maxLoopCount <= 0 || $maxLoopCount > 9999) {
                    ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.node.loop.max_loop_count_format_error', ['min' => 1, 'max' => 9999]);
                }
                $maxLoopCount = (int) $maxLoopCount;
                $loopCount = 0;
                while ($condition) {
                    $this->runLoopFlow($loopFlow, $executionData);
                    if ($executionData->variableGet($breakVariableKey, false)) {
                        break;
                    }
                    ++$loopCount;
                    if ($loopCount >= $maxLoopCount) {
                        break;
                    }
                    // eachtime重新计算itemitem
                    $condition = $conditionComponent->getCondition()->getResult($executionData->getExpressionFieldData());
                }
                break;
            default:
                return;
        }
    }

    private function createLoopFlow(string $bodyId, DelightfulFlowEntity $delightfulFlow): ?DelightfulFlowEntity
    {
        $loopDelightfulFlow = clone $delightfulFlow;

        // 做区minute
        $loopDelightfulFlow->setCode($delightfulFlow->getCode() . '_loop');
        $loopDelightfulFlow->setType(Type::Loop);

        // 循环bodysectionpoint
        $bodyNode = $delightfulFlow->getNodeById($bodyId);
        if (! $bodyNode) {
            return null;
        }

        // get所have 父 id 是这循环body的sectionpoint
        $childNodes = $delightfulFlow->getNodesByParentId($bodyId);
        if (empty($childNodes)) {
            return null;
        }
        // 去except父 id property，not然willbefilter
        foreach ($childNodes as $node) {
            $meta = $node->getMeta();
            $meta['parent_id'] = '';
            $node->setMeta($meta);
        }
        // more换execute的sectionpoint
        $loopDelightfulFlow->setNodes($childNodes);

        return $loopDelightfulFlow;
    }

    private function runLoopFlow(DelightfulFlowEntity $loopDelightfulFlow, ExecutionData $executionData): void
    {
        try {
            $subExecutor = new DelightfulFlowExecutor($loopDelightfulFlow, $executionData);
            $subExecutor->setInLoop(true);
            // 复usecurrent的executedata，循环bodyinside可access和modify
            $subExecutor->execute(TriggerType::LoopStart);
        } catch (Throwable $throwable) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.node.loop.loop_flow_execute_failed', ['error' => $throwable->getMessage()]);
        }
        // sectionpointinside部的exceptionin node 的 debug infomiddlerecord
        foreach ($loopDelightfulFlow->getNodes() as $node) {
            if ($node->getNodeDebugResult() && ! $node->getNodeDebugResult()->isSuccess()) {
                ExceptionBuilder::throw(FlowErrorCode::ExecuteFailed, 'flow.node.loop.loop_flow_execute_failed', ['error' => $node->getNodeDebugResult()->getErrorMessage()]);
            }
        }
    }
}
