<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Flow\ExecuteManager;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\DelightfulFlowExecutor;
use App\Domain\Flow\Entity\DelightfulFlowEntity;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Domain\Flow\Entity\ValueObject\Type;
use App\Infrastructure\Core\Dag\VertexResult;

/**
 * @internal
 */
class DelightfulFlowExecutorTest extends ExecuteManagerBaseTest
{
    public function testRun()
    {
        $nodes = [];
        $nodeTypes = NodeType::cases();
        foreach ($nodeTypes as $i => $nodeType) {
            $node = new Node($nodeType);
            $node->setNodeId('node_' . $i);
            $node->setName($nodeType->name);
            if (isset($nodeTypes[$i + 1])) {
                $node->setNextNodes(['node_' . ($i + 1)]);
            }
            $node->setCallback(function (VertexResult $vertexResult, ExecutionData $executionData, array $frontResults) {});
            $nodes[$i] = $node;
        }
        $delightfulFlowEntity = $this->getDelightfulFlowEntity();
        $delightfulFlowEntity->setNodes($nodes);

        $executionData = $this->createExecutionData(TriggerType::ChatMessage);
        $executor = new DelightfulFlowExecutor($delightfulFlowEntity, $executionData);

        $executor->execute();
        foreach ($nodes as $node) {
            $this->assertTrue($node->getNodeDebugResult()->isSuccess());
        }
    }

    private function getDelightfulFlowEntity(): DelightfulFlowEntity
    {
        $delightfulFlowEntity = new DelightfulFlowEntity();
        $delightfulFlowEntity->setCode('unit_test.' . uniqid());
        $delightfulFlowEntity->setName('unit_test');
        $delightfulFlowEntity->setType(Type::Main);
        $delightfulFlowEntity->setCreator('system_unit');
        return $delightfulFlowEntity;
    }
}
