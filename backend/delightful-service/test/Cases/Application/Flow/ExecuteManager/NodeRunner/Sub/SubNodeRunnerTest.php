<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Flow\ExecuteManager\NodeRunner\Sub;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\DelightfulFlowExecutor;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Dag\VertexResult;
use Connector\Component\ComponentFactory;
use HyperfTest\Cases\Application\Flow\ExecuteManager\ExecuteManagerBaseTest;

/**
 * @internal
 */
class SubNodeRunnerTest extends ExecuteManagerBaseTest
{
    public function testRun()
    {
        $this->markTestSkipped('这里needdatabase存in这个process，暂时skip');
        $node = Node::generateTemplate(NodeType::Sub, [
            'sub_flow_id' => 'DELIGHTFUL-FLOW-66d56f57e7b400-71937460',
        ]);
        $input = new NodeInput();
        $input->setForm(ComponentFactory::fastCreate(json_decode(
            <<<'JSON'
{
            "id": "component-662617c1a0884",
            "version": "1",
            "type": "form",
            "structure": {
                "type": "object",
                "key": "root",
                "sort": 0,
                "title": null,
                "description": null,
                "required": [
                    "input"
                ],
                "value": null,
                "items": null,
                "properties": {
                    "input": {
                        "type": "string",
                        "key": "input",
                        "sort": 0,
                        "title": "input",
                        "description": "",
                        "required": null,
                        "value": {
                            "type": "expression",
                            "expression_value": [
                                {
                                    "type": "fields",
                                    "value": "DELIGHTFUL-FLOW-NODE-662617c1a07615-9318288811.content",
                                    "name": "input",
                                    "args": null
                                }
                            ],
                            "const_value": null
                        },
                        "items": null,
                        "properties": null
                    }
                }
            }
        }
JSON,
            true
        )));
        $output = new NodeOutput();
        $output->setForm(ComponentFactory::fastCreate(json_decode(
            <<<'JSON'
{
    "id": "component-662617a69868d",
    "version": "1",
    "type": "form",
    "structure": {
        "type": "object",
        "key": "root",
        "sort": 0,
        "title": null,
        "description": null,
        "required": [],
        "value": null,
        "items": null,
        "properties": {
            "output": {
                "type": "string",
                "key": "output",
                "sort": 0,
                "title": "output",
                "description": "",
                "required": null,
                "value": null,
                "items": null,
                "properties": null
            }
        }
    }
}
JSON,
            true
        )));
        $node->setInput($input);
        $node->setOutput($output);
        $node->validate();

        $node->setCallback(function (VertexResult $vertexResult, ExecutionData $executionData, array $fontResults) {});

        $runner = DelightfulFlowExecutor::getNodeRunner($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('DELIGHTFUL-FLOW-NODE-662617c1a07615-9318288811', [
            'content' => '你好吗',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
