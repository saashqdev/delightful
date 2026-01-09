<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\End;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunner;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\End\EndNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;

#[FlowNodeDefine(
    type: NodeType::End->value,
    code: NodeType::End->name,
    name: 'endsectionpoint',
    paramsConfig: EndNodeParamsConfig::class,
    version: 'v0',
    singleDebug: true,
    needInput: true,
    needOutput: true,
)]
class EndNodeRunner extends NodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        $result = [];
        $output = $this->node->getOutput()?->getForm()?->getForm();
        if ($output) {
            $result = $output->getKeyValue($executionData->getExpressionFieldData());
        }

        // endsectionpointback,notexecuteback続sectionpoint
        $vertexResult->setChildrenIds([]);
        $vertexResult->setResult($result);
        $executionData->saveNodeContext($this->node->getNodeId(), $result);

        // autostatesettingendsectionpoint id
        $executionData->getDelightfulFlowEntity()?->setEndNode($this->node);
    }
}
