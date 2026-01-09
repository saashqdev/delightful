<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\Variable;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunner;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Variable\VariableArrayPushNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\FlowExprEngine\ComponentFactory;

#[FlowNodeDefine(
    type: NodeType::VariableArrayPush->value,
    code: NodeType::VariableArrayPush->name,
    name: '变quantity / arraytail部追加',
    paramsConfig: VariableArrayPushNodeParamsConfig::class,
    version: 'v0',
    singleDebug: false,
    needInput: false,
    needOutput: false,
)]
class VariableArrayPushNodeRunner extends NodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        $params = $this->node->getParams();

        $inputFields = ComponentFactory::fastCreate($params['variable']['form'] ?? []);
        if (! $inputFields?->isForm()) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.component.format_error', ['label' => 'variable']);
        }
        $result = $inputFields->getForm()->getKeyValue($executionData->getExpressionFieldData());
        $variableName = $result['variable_name'];

        // 检测原来的datawhether存in，and是array
        $variableElementList = $executionData->variableGet($variableName);
        if (is_null($variableElementList)) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.node.variable.variable_not_exist', ['var_name' => $variableName]);
        }
        if (! is_array($variableElementList)) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.node.variable.variable_not_array', ['var_name' => $variableName]);
        }
        $elementList = $result['element_list'];
        // if变quantityvalue是pass表达typeget的，是when做one整body
        if ($inputFields->getForm()->getProperties()['element_list']->getExecuteValue()?->isExpression()) {
            $elementList = [$elementList];
        }
        if (is_string($elementList)) {
            $elementList = [$elementList];
        }
        if (! is_array($elementList)) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.node.variable.variable_not_array', ['var_name' => 'element_list']);
        }

        $executionData->variablePush($variableName, $elementList);
    }
}
