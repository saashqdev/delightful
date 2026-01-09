<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Flow\ExecuteManager\NodeRunner\LLM;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunnerFactory;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Dag\VertexResult;
use Connector\Component\ComponentFactory;
use HyperfTest\Cases\Application\Flow\ExecuteManager\ExecuteManagerBaseTest;

/**
 * @internal
 */
class LLMCallNodeRunnerTest extends ExecuteManagerBaseTest
{
    public function testRun()
    {
        $node = Node::generateTemplate(NodeType::LLMCall, json_decode(<<<'JSON'
{
    "model": {
        "id": "component-66c6f20f1cc8b",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "const_value": null,
            "expression_value": [
                {
                    "type": "input",
                    "value": "gpt-4o-global",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "system_prompt": {
        "id": "component-66470a8b547b2",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "expression_value": [
                {
                    "type": "input",
                    "value": "# role\r\n你是一位极其专业且富有责任心的访客系统录入员，秉持严谨缜密的态度开展访客系统的重要inforecord工作。\r\n\r\n## 技能\r\n### 技能 1: 精准的info录入\r\n1. 当有访客进行登记时，全面细致地询问并精确record访客姓名、联系方式、来访time等关键info，同时ensure来访time为未来time，且联系方式正常，如 110 这类报警电话不可用。\r\n2. 保证录入info百分之百准确无误且完整无缺。\r\n\r\n### 技能 2: 细致的info核验\r\n1. 录入complete后，仔细check已录入的info，决不容许出现任何error或遗漏。若发现有误，当即更正。若有遗漏，请引导user填写。\r\n2. confirm访客姓名、联系方式、来访timeuser都已经完整填写，按照能直接进行 json_decode 的 json formatoutputdata，如 {\"name\":\"小李\",\"phone\":\"13800138000\",\"time\":\"20240517 15:30\"}，不allow有其他字符。针对非标准format的来访time，进行format统一转换。\r\n\r\n### 技能 3: 热忱的帮助供给\r\n1. 倘若访客对登记process存在疑问，务必耐心解答。\r\n2. 给予访客必要的引导和协助。\r\n\r\n## 限制\r\n- 专注process与访客系统有关的info，不涉及其他事项。\r\n- 严格遵循info的保密性与安全性原则。\r\n- 平等公正地对待每一位访客，持续提供高品质service。\r\n\r\n总结：访客系统录入员需专业、严谨、细致、热情，精准录入核验info，提供优质service。^^以上content援引自访客系统相关规定。",
                    "name": "",
                    "args": null
                }
            ],
            "const_value": null
        }
    },
    "messages": {
        "id": "component-66dad9f890c80",
        "version": "1",
        "type": "form",
        "structure": {
            "type": "array",
            "key": "root",
            "sort": 0,
            "title": "historymessage",
            "description": "",
            "required": null,
            "value": null,
            "items": {
                "type": "object",
                "key": "messages",
                "sort": 0,
                "title": "historymessage",
                "description": "",
                "required": [
                    "role",
                    "content"
                ],
                "value": null,
                "items": null,
                "properties": {
                    "role": {
                        "type": "string",
                        "key": "role",
                        "sort": 0,
                        "title": "role",
                        "description": "",
                        "required": null,
                        "value": null,
                        "items": null,
                        "properties": null
                    },
                    "content": {
                        "type": "string",
                        "key": "content",
                        "sort": 1,
                        "title": "content",
                        "description": "",
                        "required": null,
                        "value": null,
                        "items": null,
                        "properties": null
                    }
                }
            },
            "properties": null
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
                    "value": "9527.content",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "model_config": {
        "temperature": 0.5
    }
}
JSON, true));

        $output = new NodeOutput();
        $output->setForm(ComponentFactory::fastCreate(json_decode(<<<'JSON'
{
    "id": "component-662617c744ed6",
    "version": "1",
    "type": "form",
    "structure": {
        "type": "object",
        "key": "root",
        "sort": 0,
        "title": "root节点",
        "description": "",
        "required": [
            "text"
        ],
        "value": null,
        "items": null,
        "properties": {
            "text": {
                "type": "string",
                "key": "text",
                "sort": 0,
                "title": "文本",
                "description": "",
                "required": null,
                "value": null,
                "items": null,
                "properties": null
            }
        }
    }
}
JSON, true)));
        $node->setOutput($output);
        $node->validate();

        // 这里是为了单测
        $node->setCallback(function (VertexResult $vertexResult, ExecutionData $executionData, array $fontResults) {
            $result = [
                'text' => 'response',
            ];

            $vertexResult->setResult($result);
        });

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'content' => '你好，你是谁',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
        $this->assertIsString($vertexResult->getResult()['text']);
    }
}
