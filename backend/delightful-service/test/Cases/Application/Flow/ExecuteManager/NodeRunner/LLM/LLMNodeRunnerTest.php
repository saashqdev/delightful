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
            'content' => 'yougood,youis谁',
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
                    "value": "今daytimeis:",
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
            'content' => '今dayiswhich day',
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
                    "value": "# role\r\nyouisonehigh positionitsprofessionaland富haveresponsibilitycorevisitorsystementer员,upholdrigorous缜密statedegreeopen展visitorsystem重wantinforecordwork.\r\n\r\n## 技can\r\n### 技can 1: preciseinfoenter\r\n1. whenhavevisitorconductregistero clock,allsurfacemeticulousgroundaskandpreciserecordvisitorname,contactmethod,come访timeetcclosekeyinfo,meanwhileensurecome访timefornotcometime,andcontactmethodnormal,如 110 thiscategoryalertphonenotcanuse.\r\n2. guaranteeenterinfohundredminute之hundredaccurateno误andcompleteno缺.\r\n\r\n### 技can 2: meticulousinfoverify\r\n1. entercompleteback,carefulcheckalreadyenterinfo,决notallowout现anyerrororomit.若hair现have误,when即morejust.若haveomit,please guideuserfill in.\r\n2. confirmvisitorname,contactmethod,come访timeuserallalready经completefill in,according tocandirectlyconduct json_decode  json formatoutputdata,如 {\"name\":\"small李\",\"phone\":\"13800138000\",\"time\":\"20240517 15:30\"},notallowhaveothercharacter.针tononstandardformatcome访time,conductformat统oneconvert.\r\n\r\n### 技can 3: enthusiasmhelp供give\r\n1. ifvisitortoregisterprocess存inquestion,must be patientcoreanswer.\r\n2. give予visitor必wantguideandassist.\r\n\r\n## limit\r\n- focushandleandvisitorsystemhavecloseinfo,not涉andother事item.\r\n- strictfollowinfoconfidentialpropertyandsecurityproperty原then.\r\n- 平etc公justgroundto待eachone位visitor,continueprovidehigh品qualityservice.\r\n\r\nsummary:visitorsystementerstaff needsprofessional,rigorous,meticulous,enthusiastic,preciseenterverifyinfo,provide优qualityservice.^^byupcontentcitefromvisitorsystem相closeregulation.",
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

        // thiswithinisforsingle测
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
            'content' => 'yougood,youis谁',
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
                    "value": "youisone AI 助hand.whenuserneed资讯whendayday气o clock,call today_weather comequeryresult",
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
            'input' => '今day广州day气how',
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
                    "value": "youisone旅lineexpert,specializedresponsiblerandomtravelbody验,whenuser提towantgotravelo clock,youneed先useget_rand_citygettoonerandomcity,然backaccording tocitynamemeanwhilecallget_foods_by_city,get_place_by_city.finalgenerateonetravelsolution",
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
                                        "value": "嘻嘻",
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
            'input' => 'I想outgo玩oneday',
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
