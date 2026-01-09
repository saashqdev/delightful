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
            'content' => 'yougood，youis谁',
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
                    "value": "今daytimeis：",
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
            'content' => '今dayis星期几',
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
                    "value": "# role\r\nyouisone位极its专业and富haveresponsibilitycore访客system录入员，秉持严谨缜密statedegreeopen展访客system重wantinforecordwork。\r\n\r\n## 技can\r\n### 技can 1: 精准info录入\r\n1. whenhave访客conductregistero clock，allsurface细致groundaskandpreciserecord访客姓名、联系method、come访timeetcclosekeyinfo，meanwhileensurecome访timefornotcometime，and联系methodnormal，如 110 thiscategory报警phonenotcanuse。\r\n2. guarantee录入infohundredminute之hundredaccurateno误andcompleteno缺。\r\n\r\n### 技can 2: 细致info核验\r\n1. 录入completeback，仔细checkalready录入info，决not容许out现anyerroror遗漏。若hair现have误，when即morejust。若have遗漏，请引导user填写。\r\n2. confirm访客姓名、联系method、come访timeuserallalready经complete填写，按照can直接conduct json_decode  json formatoutputdata，如 {\"name\":\"small李\",\"phone\":\"13800138000\",\"time\":\"20240517 15:30\"}，notallowhaveothercharacter。针tononstandardformatcome访time，conductformat统oneconvert。\r\n\r\n### 技can 3: 热忱help供give\r\n1. 倘若访客toregisterprocess存in疑问，务必耐core解答。\r\n2. give予访客必want引导and协助。\r\n\r\n## limit\r\n- 专注handleand访客systemhavecloseinfo，not涉andother事item。\r\n- 严格followinfo保密propertyandsecurityproperty原then。\r\n- 平etc公justgroundto待eachone位访客，continueprovidehigh品qualityservice。\r\n\r\n总结：访客system录入员需专业、严谨、细致、热情，精准录入核验info，provide优qualityservice。^^byupcontent援引from访客system相close规定。",
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
            'content' => 'yougood，youis谁',
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
                    "value": "youisone AI 助hand。whenuserneed资讯whendayday气o clock，call today_weather comequeryresult",
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
            'input' => '今day广州day气如何',
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
                    "value": "youisone旅line专家，专门负责随机旅游body验，whenuser提towantgo旅游o clock，youneed先useget_rand_citygettoone随机city，然backaccording tocitynamemeanwhilecallget_foods_by_city，get_place_by_city。finalgenerateone旅游solution",
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
                            "title": "随机 ID",
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
            'content' => 'thiswithinsurfacehave什么color',
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
            'content' => 'thiswithinsurfacehave什么color',
        ]);
        $runner->execute($vertexResult, $executionData, []);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
