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
            'content' => 'Q: 如何做to好汇报？ A: can阅读 [述职思维导graphv1.1.2](https://xxxxx.com/docx/605565507182194688) orcontinue阅读down文； 即，我passreturn答哪theseissuecan达become「说clear我做什么、做怎么样」 issue清single (青春版) 1. ingoyear/up季degree，你main负责or参andproject，ineach阶segment原定planis怎样？thistheseplanmiddle你所负责部minuteall按o clockcomplete？finalactual落ground情况如何？ a. ifprojectcompletevery好，this好resultand你所做哪these努力haveclose？ b. ifprojectcompletenot好，存inissueand你反思is什么？你is如何improvement？improvementresult如何？ 2. passgooneyearwithin，你allfor你team做什么事情？付out什么？thisthese事情and付outresultis如何？好in哪within？not好is什么reason？for什么？ 3. passgooneyearwithin，你allfor你from己all做什么事情？付out什么？thisthese事情and付outresultis如何？好in哪within？not好is什么reason？for什么？ 4. 明yearuphalfyearplanis什么？',
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
            'content' => 'Q: 我for什么wantin述职准备upflowertime？ A: false设你本身work产outis90minute，one好述职呈现canwill你this 90 minutework完美ground呈现give所haveperson，butoneincontent结构、validinfoquantityandcan读propertyupallnot尽如person意 PPT maybe只can呈现out你30minuteworkbecomefruit。 andand，if你in述职表达and呈现方surface做verynot尽如person意、nothavebody现out结构化思考method、nothave SMART notcome规划，that么同样，你inday常departmentwill议middle沟通协ascan力、workmiddle逻辑思维can力、from我管理and规划can力alsowill备受quality疑。 述职oftenisonepersonday常forperson处事statedegree良好影射，优秀personoftennot吝atto公众showfrom己becomefruit，thisalsoisfor什么like GitHub this样open源社区will存in，and汇聚大quantity优秀person才。 meanwhile，述职alsoisonenon常珍贵、let你have机will解its它personin做什么场域。',
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
            'content' => 'Q: 我for什么wantin述职准备upflowertime？ A: false设你本身work产outis90minute，one好述职呈现canwill你this 90 minutework完美ground呈现give所haveperson，butoneincontent结构、validinfoquantityandcan读propertyupallnot尽如person意 PPT maybe只can呈现out你30minuteworkbecomefruit。 andand，if你in述职表达and呈现方surface做verynot尽如person意、nothavebody现out结构化思考method、nothave SMART notcome规划，that么同样，你inday常departmentwill议middle沟通协ascan力、workmiddle逻辑思维can力、from我管理and规划can力alsowill备受quality疑。 述职oftenisonepersonday常forperson处事statedegree良好影射，优秀personoftennot吝atto公众showfrom己becomefruit，thisalsoisfor什么like GitHub this样open源社区will存in，and汇聚大quantity优秀person才。 meanwhile，述职alsoisonenon常珍贵、let你have机will解its它personin做什么场域。',
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
            'content' => 'Q: 我for什么wantin述职准备upflowertime？ A: false设你本身work产outis90minute，one好述职呈现canwill你this 90 minutework完美ground呈现give所haveperson，butoneincontent结构、validinfoquantityandcan读propertyupallnot尽如person意 PPT maybe只can呈现out你30minuteworkbecomefruit。 andand，if你in述职表达and呈现方surface做verynot尽如person意、nothavebody现out结构化思考method、nothave SMART notcome规划，that么同样，你inday常departmentwill议middle沟通协ascan力、workmiddle逻辑思维can力、from我管理and规划can力alsowill备受quality疑。 述职oftenisonepersonday常forperson处事statedegree良好影射，优秀personoftennot吝atto公众showfrom己becomefruit，thisalsoisfor什么like GitHub this样open源社区will存in，and汇聚大quantity优秀person才。 meanwhile，述职alsoisonenon常珍贵、let你have机will解its它personin做什么场域。',
            'business_id' => '',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
