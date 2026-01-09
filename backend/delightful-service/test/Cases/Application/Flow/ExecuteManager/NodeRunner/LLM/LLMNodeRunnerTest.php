<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Flow\ExecuteManager\NodeRunner\LLM;

use App\Application\Flow\ExecuteManager\Attachment\ExternalAttachment;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunnerFactory;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Dag\VertexResult;
use Delightful\FlowExprEngine\ComponentFactory;
use HyperfTest\Cases\Application\Flow\ExecuteManager\ExecuteManagerBaseTest;

/**
 * @internal
 */
class LLMNodeRunnerTest extends ExecuteManagerBaseTest
{
    public function testRunSimple()
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
            "expression_value": null,
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
                    "value": "9527.content",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "model_config": {
        "temperature": 0.5,
        "max_record": 10
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

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'content' => 'yougood,youiswho',
        ]);
        $runner->execute($vertexResult, $executionData, []);

        $this->assertIsString($vertexResult->getResult()['text']);
    }

    public function testRunGetDate()
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
                    "type": "input",
                    "value": "todaydaytimeis:",
                    "name": "",
                    "args": null
                },
                {
                    "type": "methods",
                    "value": "get_rfc1123_date_time",
                    "name": "get_rfc1123_date_time",
                    "args": []
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
                    "value": "9527.content",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "model_config": {
        "temperature": 0.5,
        "max_record": 10
    }
}
JSON, true));

        $output = new NodeOutput();
        $output->setForm(ComponentFactory::generateTemplate(StructureType::Form));
        $node->setOutput($output);
        $node->validate();

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'content' => 'todaydayiswhich day',
        ]);
        $runner->execute($vertexResult, $executionData, []);

        $this->assertIsString($vertexResult->getResult()['text']);
    }

    public function testRun()
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
                    "type": "input",
                    "value": "# role\r\nyouisonehigh positionitsprofessionaland富haveresponsibilitycorevisitorsystementermember,upholdrigorousmeticulousstatedegreeopen展visitorsystemreloadwantinforecordwork.\r\n\r\n## skillcan\r\n### skillcan 1: preciseinfoenter\r\n1. whenhavevisitorconductregistero clock,allsurfacemeticulousgroundaskandpreciserecordvisitorname,contactmethod,comevisittimeetcclosekeyinfo,meanwhileensurecomevisittimefornotcometime,andcontactmethodnormal,like 110 thiscategoryalertphonenotcanuse.\r\n2. guaranteeenterinfohundredminuteofhundredaccuratenoerrorandcompleteno缺.\r\n\r\n### skillcan 2: meticulousinfoverify\r\n1. entercompleteback,carefulcheckalreadyenterinfo,决notallowoutshowanyerrororomit.ifhairshowhaveerror,whenimmediatelymorejust.ifhaveomit,please guideuserfill in.\r\n2. confirmvisitorname,contactmethod,comevisittimeuserallalreadyalreadycompletefill in,according tocandirectlyconduct json_decode  json formatoutputdata,like {\"name\":\"small李\",\"phone\":\"13800138000\",\"time\":\"20240517 15:30\"},notallowhaveothercharacter.needletononstandardformatcomevisittime,conductformatsystemoneconvert.\r\n\r\n### skillcan 3: enthusiasmhelpsupplygive\r\n1. ifvisitortoregisterprocessexistsinquestion,must be patientcoreanswer.\r\n2. give予visitorrequiredwantguideandassist.\r\n\r\n## limit\r\n- focushandleandvisitorsystemhavecloseinfo,notinvolveandotherthingitem.\r\n- strictfollowinfoconfidentialpropertyandsecuritypropertyoriginalthen.\r\n- 平etcpublicjustgroundtopendingeachonepositionvisitor,continueprovidehighproductqualityservice.\r\n\r\nsummary:visitorsystementerstaff needsprofessional,rigorous,meticulous,enthusiastic,preciseenterverifyinfo,provide优qualityservice.^^byupcontentcitefromvisitorsystemrelatedcloseregulation.",
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
                    "value": "9527.content",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "model_config": {
        "temperature": 0.5
    },
    "max_record": 10
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
        //        $node->setCallback(function (VertexResult $vertexResult, ExecutionData $executionData, array $fontResults) {
        //            $result = [
        //                'text' => 'response',
        //            ];
        //
        //            $vertexResult->setResult($result);
        //        });

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'content' => 'yougood,youiswho',
        ]);
        $runner->execute($vertexResult, $executionData, []);

        $this->assertIsString($vertexResult->getResult()['text']);
    }

    public function testTools()
    {
        $node = Node::generateTemplate(NodeType::LLM, json_decode(<<<'JSON'
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
                    "value": "youisone AI helphand.whenuserneedinformationwhendaydayairo clock,call today_weather comequeryresult",
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
                    "value": "9527.input",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "model_config": {
        "temperature": 0.5
    },
    "max_record": 10,
    "tools": ["DELIGHTFUL-FLOW-668247acbde108-54216815"]
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

        //        $node->setCallback(function (VertexResult $vertexResult, ExecutionData $executionData, array $fontResults) {
        //            $result = [
        //                'text' => 'response',
        //            ];
        //
        //            $vertexResult->setResult($result);
        //        });

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'input' => 'todaydayGuangzhoudayairhow',
        ]);
        $runner->execute($vertexResult, $executionData, []);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }

    public function testOptionTools()
    {
        $node = Node::generateTemplate(NodeType::LLM, json_decode(<<<'JSON'
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
                    "value": "youisone旅lineexpert,specializedresponsiblerandomtravelbody验,whenusersubmittowantgotravelo clock,youneedfirstuseget_rand_citygettoonerandomcity,thenbackaccording tocitynamemeanwhilecallget_foods_by_city,get_place_by_city.finalgenerateonetravelsolution",
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
                    "value": "9527.input",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "model_config": {
        "temperature": 0.5,
        "max_record": 10
    },
    "option_tools":[
      {
        "tool_id": "DELIGHTFUL-FLOW-6735ef22377435-40152226",
        "tool_set_id": "TOOL-SET-6725c6f73b8485-86291897",
        "async": false,
        "custom_system_input": {
            "widget": null,
            "form": {
                "id": "components-epRzifiK",
                "version": "1",
                "type": "form",
                "structure": {
                    "title": "",
                    "description": "",
                    "value": null,
                    "encryption": false,
                    "type": "object",
                    "properties": {
                        "id": {
                            "type": "string",
                            "title": "random ID",
                            "description": "",
                            "value": {
                                "type": "expression",
                                "const_value": null,
                                "expression_value": [
                                    {
                                        "type": "input",
                                        "value": "hehe",
                                        "name": "",
                                        "args": null
                                    }
                                ]
                            },
                            "encryption": false
                        }
                    },
                    "required": []
                }
            }
        }
      },
      {
        "tool_id": "DELIGHTFUL-FLOW-6735ef77eb3086-30338119",
        "tool_set_id": "TOOL-SET-6725c6f73b8485-86291897",
        "async": false,
        "custom_system_input": null
      },
      {
        "tool_id": "DELIGHTFUL-FLOW-6735f03845d901-08510986",
        "tool_set_id": "TOOL-SET-6725c6f73b8485-86291897",
        "async": false,
        "custom_system_input": null
      }
    ]
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

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'input' => 'Ithinkoutgo玩oneday',
        ]);
        $runner->execute($vertexResult, $executionData, []);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }

    public function testRunImage()
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
            "expression_value": null,
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
                    "value": "9527.content",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "model_config": {
        "temperature": 0.5,
        "max_record": 10
    }
}
JSON, true), 'v1');

        $output = new NodeOutput();
        $output->setForm(ComponentFactory::generateTemplate(StructureType::Form));
        $node->setOutput($output);

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->getTriggerData()->addAttachment(new ExternalAttachment('https://example.tos-cn-beijing.volces.com/DELIGHTFUL/test/a8eb01e6fc604e8f30521f7e3b4df449.jpeg'));
        $executionData->saveNodeContext('9527', [
            'content' => 'thiswithinsurfacehavewhatcolor',
        ]);
        $runner->execute($vertexResult, $executionData, []);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }

    public function testRunImageCannot()
    {
        $node = Node::generateTemplate(NodeType::LLM, json_decode(<<<'JSON'
{
    "model": "DeepSeek-R1",
    "system_prompt": {
        "id": "component-66470a8b547b2",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "expression_value": null,
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
                    "value": "9527.content",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "model_config": {
        "temperature": 0.5,
        "max_record": 10
    }
}
JSON, true), 'v1');

        $output = new NodeOutput();
        $output->setForm(ComponentFactory::generateTemplate(StructureType::Form));
        $node->setOutput($output);

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->getTriggerData()->addAttachment(new ExternalAttachment('https://example.tos-cn-beijing.volces.com/DELIGHTFUL/test/a8eb01e6fc604e8f30521f7e3b4df449.jpeg'));
        $executionData->saveNodeContext('9527', [
            'content' => 'thiswithinsurfacehavewhatcolor',
        ]);
        $runner->execute($vertexResult, $executionData, []);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
