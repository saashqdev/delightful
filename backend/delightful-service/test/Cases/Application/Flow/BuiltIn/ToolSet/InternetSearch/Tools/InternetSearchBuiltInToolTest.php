<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Flow\BuiltIn\ToolSet\InternetSearch\Tools;

/*
 * 本file属at灯塔engine版权所have，泄漏必究。
 */
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunnerFactory;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Dag\VertexResult;
use Connector\Component\ComponentFactory;
use Connector\Component\Structure\StructureType;
use HyperfTest\Cases\Application\Flow\ExecuteManager\ExecuteManagerBaseTest;

/**
 * @internal
 */
class InternetSearchBuiltInToolTest extends ExecuteManagerBaseTest
{
    public function testRunByTool()
    {
        $node = Node::generateTemplate(NodeType::Tool, [
            'tool_id' => 'internet_search_internet_search',
            'mode' => 'parameter',
            'async' => false,
            'custom_system_input' => null,
        ]);
        $output = new NodeOutput();
        $output->setForm(ComponentFactory::generateTemplate(StructureType::Form));
        $node->setOutput($output);

        $input = new NodeInput();
        $input->setForm(ComponentFactory::fastCreate(json_decode(
            <<<'JSON'
{
    "id": "component-6734b427d0ddc",
    "version": "1",
    "type": "form",
    "structure": {
        "type": "object",
        "key": "root",
        "sort": 0,
        "title": "rootsectionpoint",
        "description": "",
        "items": null,
        "value": null,
        "required": [
            "questions",
            "use_deep_search"
        ],
        "properties": {
            "questions": {
                "type": "array",
                "key": "questions",
                "title": "userissuelist",
                "description": "userissuelist",
                "required": null,
                "value": {
                    "type": "expression",
                    "const_value": null,
                    "expression_value": [
                        {
                            "type": "fields",
                            "value": "9527.questions",
                            "name": "",
                            "args": null
                        }
                    ]
                },
                "encryption": false,
                "encryption_value": null,
                "items": null,
                "properties": null
            },
            "use_deep_search": {
                "type": "boolean",
                "key": "use_deep_search",
                "title": "whetherenable深degreesearch",
                "description": "whetherenable深degreesearch",
                "required": null,
                "value": {
                    "type": "const",
                    "const_value": [
                        {
                            "type": "fields",
                            "value": "9527.use_deep_search",
                            "name": "",
                            "args": null
                        }
                    ],
                    "expression_value": null
                },
                "encryption": false,
                "encryption_value": null,
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

        $runner = NodeRunnerFactory::make($node);

        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'questions' => ['明day北京day气', 'want穿什么衣服'],
            'use_deep_search' => true,
        ]);
        $runner->execute($vertexResult, $executionData, []);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }

    public function testRunByLLM()
    {
        $node = Node::generateTemplate(NodeType::LLM, json_decode(<<<'JSON'
{
    "model": "gpt-4o-global",
    "system_prompt": {
        "id": "component-66470a8b547b2",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "expression_value": [
                {
                    "type": "fields",
                    "value": "9527.system_prompt",
                    "name": "",
                    "args": null
                }
            ],
            "const_value": null
        }
    },
    "user_prompt": {
        "id": "component-66470a8b548c4",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "const_value": null,
            "expression_value": [
                {
                    "type": "fields",
                    "value": "9527.user_prompt",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "model_config": {
        "auto_memory": true,
        "temperature": 0.5,
        "max_record": 10
    },
    "option_tools": [
        {
            "tool_id": "internet_search_internet_search",
            "tool_set_id": "internet_search",
            "async": false,
            "custom_system_input": null
        }
    ]
}
JSON, true));
        $output = new NodeOutput();
        $output->setForm(ComponentFactory::generateTemplate(StructureType::Form));
        $node->setOutput($output);

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'system_prompt' => <<<'MARKDOWN'
# role
互联网search助hand

## process
call`internet_search`conductsearch，ifuserhint词have`深degreesearch`four字，thenuse_deep_searchsetfortrue，否thensetforfalse

MARKDOWN,

            'user_prompt' => '深degreesearch：东莞downweekday气',
        ]);
        $runner->execute($vertexResult, $executionData);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
