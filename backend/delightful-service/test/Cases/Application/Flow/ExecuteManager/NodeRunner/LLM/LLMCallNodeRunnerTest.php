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
                    "value": "# role\r\nyouisonehigh positionitsprofessionalandrichhaveresponsibilitycorevisitorsystementermember,upholdrigorousmeticulousstatedegreeopenexpandvisitorsystemreloadwantinforecordwork.\r\n\r\n## skillcan\r\n### skillcan 1: preciseinfoenter\r\n1. whenhavevisitorconductregistero clock,allsurfacemeticulousgroundaskandpreciserecordvisitorname,contactmethod,comevisittimeetcclosekeyinfo,meanwhileensurecomevisittimefornotcometime,andcontactmethodnormal,like 110 thiscategoryalertphonenotcanuse.\r\n2. guaranteeenterinfohundredminuteofhundredaccuratenoerrorandcompleteno缺.\r\n\r\n### skillcan 2: meticulousinfoverify\r\n1. entercompleteback,carefulcheckalreadyenterinfo,决notallowoutshowanyerrororomit.ifhairshowhaveerror,whenimmediatelymorejust.ifhaveomit,please guideuserfill in.\r\n2. confirmvisitorname,contactmethod,comevisittimeuserallalreadyalreadycompletefill in,according tocandirectlyconduct json_decode  json formatoutputdata,like {\"name\":\"smallLi\",\"phone\":\"13800138000\",\"time\":\"20240517 15:30\"},notallowhaveothercharacter.needletononstandardformatcomevisittime,conductformatsystemoneconvert.\r\n\r\n### skillcan 3: enthusiasmhelpsupplygive\r\n1. ifvisitortoregisterprocessexistsinquestion,must be patientcoreanswer.\r\n2. givegivevisitorrequiredwantguideandassist.\r\n\r\n## limit\r\n- focusprocessandvisitorsystemhavecloseinfo,notinvolveandotherthingitem.\r\n- strictfollowinfoconfidentialpropertyandsecuritypropertyoriginalthen.\r\n- 平etcpublicjustgroundtopendingeachonepositionvisitor,continueprovidehighproductqualityservice.\r\n\r\nsummary:visitorsystementerstaff needsprofessional,rigorous,meticulous,enthusiastic,preciseenterverifyinfo,provide优qualityservice.^^byupcontentcitefromvisitorsystemrelatedcloseregulation.",
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

        // thiswithinisforsingletest
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
            'content' => 'yougood,youiswho',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
        $this->assertIsString($vertexResult->getResult()['text']);
    }
}
