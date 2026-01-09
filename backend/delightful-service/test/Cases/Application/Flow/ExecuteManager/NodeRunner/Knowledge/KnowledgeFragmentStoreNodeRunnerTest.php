<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Flow\ExecuteManager\NodeRunner\Knowledge;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunnerFactory;
use App\Domain\Flow\Entity\ValueObject\ConstValue;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Dag\VertexResult;
use HyperfTest\Cases\Application\Flow\ExecuteManager\ExecuteManagerBaseTest;

/**
 * @internal
 */
class KnowledgeFragmentStoreNodeRunnerTest extends ExecuteManagerBaseTest
{
    public function testRun()
    {
        $node = Node::generateTemplate(NodeType::KnowledgeFragmentStore, json_decode(<<<'JSON'
{
    "knowledge_code": "KNOWLEDGE-674d1987228b42-90330502",
    "content": {
        "id": "component-66976250058b5",
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
    "metadata": {
        "id": "component-6697625006b5d",
        "version": "1",
        "type": "form",
        "structure": {
            "type": "object",
            "key": "root",
            "sort": 0,
            "title": null,
            "description": null,
            "required": [
              "organization_code"
            ],
            "value": null,
            "items": null,
            "properties": {
                "organization_code": {
                    "type": "string",
                    "title": "",
                    "description": "",
                    "value": {
                      "type": "const",
                      "const_value": [
                        {
                          "type": "input",
                          "uniqueId": "608188910752763904",
                          "value": "DT001"
                        }
                      ],
                      "expression_value": []
                    }
                  }
            }
        }
    },
    "business_id": {
        "id": "component-66976250058b5",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "const_value": null,
            "expression_value": [
                {
                    "type": "fields",
                    "value": "9527.business_id",
                    "name": "",
                    "args": null
                }
            ]
        }
    }
}
JSON, true));
        $node->validate();

        //        $node->setCallback(function (VertexResult $vertexResult, ExecutionData $executionData, array $fontResults) {});

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'content' => 'Q: 如何做to好的汇报？ A: can阅读 [述职思维导graphv1.1.2](https://xxxxx.com/docx/605565507182194688) orcontinue阅读down文； 即，我pass回答哪些issuecan达become「说清楚我做了什么、做得怎么样」 issue清单 (青春版) 1. in去year/up季degree，你main负责or参与的project，ineach阶segment的原定的plan是怎样的？这些planmiddle你所负责的部minuteall按o clockcomplete了吗？finalactual落ground的情况如何？ a. ifprojectcomplete的very好，这好的result与你所做的哪些努力have关？ b. ifprojectcomplete的not好，存in的issue与你的反思是什么？你是如何improvement的？improvement的result如何？ 2. 过去一yearwithin，你all为你的team做了什么事情？付出了什么？这些事情和付出的result是如何？好in哪within？not好是什么reason？为什么？ 3. 过去一yearwithin，你all为你自己all做了什么事情？付出了什么？这些事情和付出的result是如何？好in哪within？not好是什么reason？为什么？ 4. 明yearup半year的plan是什么？',
            'business_id' => '',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }

    public function testRunVectorDatabaseId()
    {
        $node = Node::generateTemplate(NodeType::KnowledgeFragmentStore, json_decode(<<<'JSON'
{
    "vector_database_id": {
        "id": "component-66976250058b5",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "const_value": null,
            "expression_value": [
                {
                    "type": "fields",
                    "value": "9527.vector_database_id",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "content": {
        "id": "component-66976250058b5",
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
    "metadata": {
        "id": "component-6697625006b5d",
        "version": "1",
        "type": "form",
        "structure": {
            "type": "object",
            "key": "root",
            "sort": 0,
            "title": null,
            "description": null,
            "required": [
              "organization_code"
            ],
            "value": null,
            "items": null,
            "properties": {
                "organization_code": {
                    "type": "string",
                    "title": "",
                    "description": "",
                    "value": {
                      "type": "const",
                      "const_value": [
                        {
                          "type": "input",
                          "uniqueId": "608188910752763904",
                          "value": "DT001"
                        }
                      ],
                      "expression_value": []
                    }
                  }
            }
        }
    },
    "business_id": {
        "id": "component-66976250058b5",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "const_value": null,
            "expression_value": [
                {
                    "type": "fields",
                    "value": "9527.business_id",
                    "name": "",
                    "args": null
                }
            ]
        }
    }
}
JSON, true));
        $node->validate();

        //        $node->setCallback(function (VertexResult $vertexResult, ExecutionData $executionData, array $fontResults) {});

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'vector_database_id' => 'KNOWLEDGE-674d1987228b42-90330502',
            'content' => 'Q: 我为什么要in述职准备upflowertime？ A: 假设你本身的work产出是90minute，一好的述职呈现能将你这 90 minute的work完美ground呈现给所haveperson，but一incontent结构、validinfoquantity和可读propertyupallnot尽如person意的 PPT 可能只能呈现出你30minute的workbecomefruit。 并and，if你in述职的表达与呈现方surface做得verynot尽如person意、nothavebody现出结构化的思考method、nothave SMART 的未来规划，那么同样的，你inday常departmentwill议middle的沟通协作能力、workmiddle的逻辑思维能力、自我管理和规划的能力alsowill备受quality疑。 述职often是一personday常为person处事statedegree的良好影射，优秀的personoftennot吝atto公众show自己的becomefruit，这also是为什么像 GitHub 这样的开源社区will存in，and汇聚了大quantity的优秀person才。 meanwhile，述职also是一non常珍贵的、让你have机will了解其它personin做什么的场域。',
            'business_id' => '',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }

    public function testRunVectorDatabaseIdConstValue()
    {
        $node = Node::generateTemplate(NodeType::KnowledgeFragmentStore, json_decode(<<<'JSON'
{
    "vector_database_id": {
        "id": "component-674eb95296aa6",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "const",
            "const_value": [
                {
                    "type": "names",
                    "value": "",
                    "name": "",
                    "args": null,
                    "names_value": [
                        {
                            "id": "KNOWLEDGE-674d1987228b42-90330502",
                            "name": "testtoquantity"
                        }
                    ],
                    "uniqueId": "524116265091162112"
                }
            ]
        }
    },
    "content": {
        "id": "component-66976250058b5",
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
    "metadata": {
        "id": "component-6697625006b5d",
        "version": "1",
        "type": "form",
        "structure": {
            "type": "object",
            "key": "root",
            "sort": 0,
            "title": null,
            "description": null,
            "required": [
                "organization_code"
            ],
            "value": null,
            "items": null,
            "properties": {
                "organization_code": {
                    "type": "string",
                    "title": "",
                    "description": "",
                    "value": {
                        "type": "const",
                        "const_value": [
                            {
                                "type": "input",
                                "uniqueId": "608188910752763904",
                                "value": "DT001"
                            }
                        ],
                        "expression_value": []
                    }
                }
            }
        }
    },
    "business_id": {
        "id": "component-66976250058b5",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "const_value": null,
            "expression_value": [
                {
                    "type": "fields",
                    "value": "9527.business_id",
                    "name": "",
                    "args": null
                }
            ]
        }
    }
}
JSON, true));
        $node->validate();

        //        $node->setCallback(function (VertexResult $vertexResult, ExecutionData $executionData, array $fontResults) {});

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'content' => 'Q: 我为什么要in述职准备upflowertime？ A: 假设你本身的work产出是90minute，一好的述职呈现能将你这 90 minute的work完美ground呈现给所haveperson，but一incontent结构、validinfoquantity和可读propertyupallnot尽如person意的 PPT 可能只能呈现出你30minute的workbecomefruit。 并and，if你in述职的表达与呈现方surface做得verynot尽如person意、nothavebody现出结构化的思考method、nothave SMART 的未来规划，那么同样的，你inday常departmentwill议middle的沟通协作能力、workmiddle的逻辑思维能力、自我管理和规划的能力alsowill备受quality疑。 述职often是一personday常为person处事statedegree的良好影射，优秀的personoftennot吝atto公众show自己的becomefruit，这also是为什么像 GitHub 这样的开源社区will存in，and汇聚了大quantity的优秀person才。 meanwhile，述职also是一non常珍贵的、让你have机will了解其它personin做什么的场域。',
            'business_id' => '',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }

    public function testRunUserTopic()
    {
        $node = Node::generateTemplate(NodeType::KnowledgeFragmentStore, json_decode(<<<'JSON'
{
    "vector_database_id": {
        "id": "component-66976250058b5",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "const_value": null,
            "expression_value": [
                {
                    "type": "fields",
                    "value": "9527.vector_database_id",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "content": {
        "id": "component-66976250058b5",
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
    "metadata": {
        "id": "component-6697625006b5d",
        "version": "1",
        "type": "form",
        "structure": {
            "type": "object",
            "key": "root",
            "sort": 0,
            "title": null,
            "description": null,
            "required": [
              "organization_code"
            ],
            "value": null,
            "items": null,
            "properties": {
                "organization_code": {
                    "type": "string",
                    "title": "",
                    "description": "",
                    "value": {
                      "type": "const",
                      "const_value": [
                        {
                          "type": "input",
                          "uniqueId": "608188910752763904",
                          "value": "DT001"
                        }
                      ],
                      "expression_value": []
                    }
                  }
            }
        }
    },
    "business_id": {
        "id": "component-66976250058b5",
        "version": "1",
        "type": "value",
        "structure": {
            "type": "expression",
            "const_value": null,
            "expression_value": [
                {
                    "type": "fields",
                    "value": "9527.business_id",
                    "name": "",
                    "args": null
                }
            ]
        }
    }
}
JSON, true));
        $node->validate();

        //        $node->setCallback(function (VertexResult $vertexResult, ExecutionData $executionData, array $fontResults) {});

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'vector_database_id' => ConstValue::KNOWLEDGE_USER_CURRENT_TOPIC,
            'content' => 'Q: 我为什么要in述职准备upflowertime？ A: 假设你本身的work产出是90minute，一好的述职呈现能将你这 90 minute的work完美ground呈现给所haveperson，but一incontent结构、validinfoquantity和可读propertyupallnot尽如person意的 PPT 可能只能呈现出你30minute的workbecomefruit。 并and，if你in述职的表达与呈现方surface做得verynot尽如person意、nothavebody现出结构化的思考method、nothave SMART 的未来规划，那么同样的，你inday常departmentwill议middle的沟通协作能力、workmiddle的逻辑思维能力、自我管理和规划的能力alsowill备受quality疑。 述职often是一personday常为person处事statedegree的良好影射，优秀的personoftennot吝atto公众show自己的becomefruit，这also是为什么像 GitHub 这样的开源社区will存in，and汇聚了大quantity的优秀person才。 meanwhile，述职also是一non常珍贵的、让你have机will了解其它personin做什么的场域。',
            'business_id' => '',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
