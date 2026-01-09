<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\If;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunner;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\If\IfNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use Delightful\FlowExprEngine\ComponentFactory;

#[FlowNodeDefine(type: NodeType::If->value, code: NodeType::If->name, name: 'select器', paramsConfig: IfNodeParamsConfig::class, version: 'v0', singleDebug: false, needInput: false, needOutput: false)]
class IfNodeRunner extends NodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        $params = $this->node->getParams();

        $branches = $params['branches'] ?? [];
        if (empty($branches)) {
            $vertexResult->setChildrenIds([]);
            return;
        }

        $debug = [];

        $elseBranch = [];
        $if = false;

        $nextNodes = [];
        foreach ($branches as $branch) {
            if ($branch['branch_type'] === 'else') {
                $elseBranch = $branch;
                continue;
            }
            $component = ComponentFactory::fastCreate($branch['parameters'] ?? []);
            if (! $component?->isCondition()) {
                continue;
            }
            $condition = $component->getCondition()->getResult($executionData->getExpressionFieldData());
            if ($condition) {
                // full足itemitemthenwill走
                $nextNodes = array_merge($nextNodes, $branch['next_nodes'] ?? []);
                // 命middle if
                $if = true;
            }
            $debug[] = [
                'branch' => $branch,
                'condition' => $condition,
            ];
        }

        // ifnothave命middle if，then走else
        if (! $if && $elseBranch) {
            $nextNodes = $elseBranch['next_nodes'] ?? [];
            $debug[] = [
                'branch' => $elseBranch,
                'condition' => true,
            ];
        }
        $vertexResult->setDebugLog($debug);

        $vertexResult->setChildrenIds(array_values(array_unique($nextNodes)));
    }
}
