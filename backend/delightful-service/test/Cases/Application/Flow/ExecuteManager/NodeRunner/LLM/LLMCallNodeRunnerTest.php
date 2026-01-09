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
                    "value": "# role\r\n你是一位极其专业and富have责任core的访客system录入员，秉持严谨缜密的statedegree开展访客system的重要inforecordwork。\r\n\r\n## 技能\r\n### 技能 1: 精准的info录入\r\n1. whenhave访客conduct登记o clock，allsurface细致ground询问并精确record访客姓名、联系method、来访timeetc关键info，meanwhileensure来访time为未来time，and联系methodnormal，如 110 这category报警电话not可use。\r\n2. 保证录入info百minute之百准确无误and完整无缺。\r\n\r\n### 技能 2: 细致的info核验\r\n1. 录入completeback，仔细check已录入的info，决not容许出现任何erroror遗漏。若hair现have误，when即more正。若have遗漏，请引导user填写。\r\n2. confirm访客姓名、联系method、来访timeuserall已经完整填写，按照能直接conduct json_decode 的 json formatoutputdata，如 {\"name\":\"小李\",\"phone\":\"13800138000\",\"time\":\"20240517 15:30\"}，notallowhave其他character。针对nonstandardformat的来访time，conductformat统一convert。\r\n\r\n### 技能 3: 热忱的help供给\r\n1. 倘若访客对登记process存in疑问，务必耐core解答。\r\n2. 给予访客必要的引导和协助。\r\n\r\n## 限制\r\n- 专注process与访客systemhave关的info，not涉及其他事item。\r\n- 严格遵循info的保密property与securityproperty原then。\r\n- 平etc公正ground对待each一位访客，continue提供高品qualityservice。\r\n\r\n总结：访客system录入员需专业、严谨、细致、热情，精准录入核验info，提供优qualityservice。^^byupcontent援引自访客system相关规定。",
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
        "title": "rootsectionpoint",
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
                "title": "text",
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

        // 这within是为了单测
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
