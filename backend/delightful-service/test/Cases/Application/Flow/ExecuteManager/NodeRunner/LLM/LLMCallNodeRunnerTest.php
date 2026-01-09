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
                    "value": "# role\r\nyouisonehigh positionitsprofessionaland富haveresponsibilitycorevisitorsystementer员,upholdrigorousmeticulousstatedegreeopen展visitorsystem重wantinforecordwork.\r\n\r\n## 技can\r\n### 技can 1: preciseinfoenter\r\n1. whenhavevisitorconductregistero clock,allsurfacemeticulousgroundaskandpreciserecordvisitorname,contactmethod,come访timeetcclosekeyinfo,meanwhileensurecome访timefornotcometime,andcontactmethodnormal,如 110 thiscategoryalertphonenotcanuse.\r\n2. guaranteeenterinfohundredminute之hundredaccurateno误andcompleteno缺.\r\n\r\n### 技can 2: meticulousinfoverify\r\n1. entercompleteback,carefulcheckalreadyenterinfo,决notallowout现anyerrororomit.若hair现have误,when即morejust.若haveomit,please guideuserfill in.\r\n2. confirmvisitorname,contactmethod,come访timeuserallalready经completefill in,according tocandirectlyconduct json_decode  json formatoutputdata,如 {\"name\":\"small李\",\"phone\":\"13800138000\",\"time\":\"20240517 15:30\"},notallowhaveothercharacter.针tononstandardformatcome访time,conductformat统oneconvert.\r\n\r\n### 技can 3: enthusiasmhelp供give\r\n1. ifvisitortoregisterprocess存inquestion,must be patientcoreanswer.\r\n2. give予visitor必wantguideandassist.\r\n\r\n## limit\r\n- focusprocessandvisitorsystemhavecloseinfo,not涉andother事item.\r\n- strictfollowinfoconfidentialpropertyandsecurityproperty原then.\r\n- 平etc公justgroundto待eachone位visitor,continueprovidehigh品qualityservice.\r\n\r\nsummary:visitorsystementerstaff needsprofessional,rigorous,meticulous,enthusiastic,preciseenterverifyinfo,provide优qualityservice.^^byupcontentcitefromvisitorsystem相closeregulation.",
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

        // thiswithinisforsingle测
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
            'content' => 'yougood,youis谁',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
        $this->assertIsString($vertexResult->getResult()['text']);
    }
}
