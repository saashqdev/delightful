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
            'content' => '你好，你is谁',
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
                    "value": "# role\r\n你isone位极其专业and富have责任core访客system录入员，秉持严谨缜密statedegreeopen展访客system重要inforecordwork。\r\n\r\n## 技能\r\n### 技能 1: 精准info录入\r\n1. whenhave访客conduct登记o clock，allsurface细致ground询问andpreciserecord访客姓名、联系method、come访timeetcclose键info，meanwhileensurecome访timefor未cometime，and联系methodnormal，如 110 这category报警phonenotcanuse。\r\n2. 保证录入infohundredminute之hundredaccurateno误andcompleteno缺。\r\n\r\n### 技能 2: 细致info核验\r\n1. 录入completeback，仔细check已录入info，决not容许out现任何erroror遗漏。若hair现have误，when即more正。若have遗漏，请引导user填写。\r\n2. confirm访客姓名、联系method、come访timeuserall已经complete填写，按照能直接conduct json_decode  json formatoutputdata，如 {\"name\":\"小李\",\"phone\":\"13800138000\",\"time\":\"20240517 15:30\"}，notallowhave其他character。针tononstandardformatcome访time，conductformat统oneconvert。\r\n\r\n### 技能 3: 热忱help供give\r\n1. 倘若访客to登记process存in疑问，务必耐core解答。\r\n2. give予访客必要引导and协助。\r\n\r\n## 限制\r\n- 专注handleand访客systemhavecloseinfo，not涉and其他事item。\r\n- 严格遵循info保密propertyandsecurityproperty原then。\r\n- 平etc公正groundto待eachone位访客，continue提供高品qualityservice。\r\n\r\n总结：访客system录入员需专业、严谨、细致、热情，精准录入核验info，提供优qualityservice。^^byupcontent援引from访客system相close规定。",
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

        // 这withinisforsingle测
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
            'content' => '你好，你is谁',
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
                    "value": "你isone AI 助hand。whenuserneed资讯whendayday气o clock，call today_weather comequeryresult",
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
                    "value": "你isone旅line专家，专门负责随机旅游body验，whenuser提to要go旅游o clock，你need先useget_rand_citygettoone随机city，然backaccording tocitynamemeanwhilecallget_foods_by_city，get_place_by_city。finalgenerateone旅游solution",
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
            'input' => '我想outgo玩oneday',
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
            'content' => '这withinsurfacehave什么color',
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
            'content' => '这withinsurfacehave什么color',
        ]);
        $runner->execute($vertexResult, $executionData, []);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
