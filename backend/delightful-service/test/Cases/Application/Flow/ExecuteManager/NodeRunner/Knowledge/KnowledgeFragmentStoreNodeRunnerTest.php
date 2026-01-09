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
            'content' => 'Q: how做togoodreport? A: canread [work reportthinking导graphv1.1.2](https://xxxxx.com/docx/605565507182194688) orcontinuereaddown文; 即,Ipassreturnanswer whichtheseissuecan达become「说clearI做what,做how样」 issue清single (youth version) 1. ingoyear/up季degree,youmainresponsibleor参andproject,ineach阶segmentoriginally scheduledplanishow?thistheseplanmiddleyou所responsible部minuteall按o clockcomplete?finalactual落groundsituationhow? a. ifprojectcompleteverygood,thisgoodresultandyou所做哪these努力haveclose? b. ifprojectcompletenotgood,存inissueandyou反思iswhat?youishowimprovement?improvementresulthow? 2. passgooneyearwithin,youallforyouteam做whatthing?付outwhat?thisthesethingand付outresultishow?goodin哪within?notgoodiswhatreason?forwhat? 3. passgooneyearwithin,youallforyoufrom己all做whatthing?付outwhat?thisthesethingand付outresultishow?goodin哪within?notgoodiswhatreason?forwhat? 4. 明yearuphalfyearplaniswhat?',
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
            'content' => 'Q: Iforwhatwantinwork reportprepareupflowertime? A: false设youitselfwork产outis90minute,onegoodwork reportpresentcanwillyouthis 90 minuteworkperfectgroundpresentgive所haveperson,butoneincontentstructure,validinfoquantityandcan读propertyupallnotas best asperson意 PPT maybeonlycanpresentoutyou30minuteworkbecomefruit. andand,ifyouinwork reporttable达andpresent方surface做verynotas best asperson意,nothavebody现outstructure化thinkmethod,nothave SMART notcomeplan,thatalso,youinday常departmentwill议middlecommunication and collaborationascan力,workmiddlelogicthinkingcan力,fromImanageandplancan力alsowillhighlyquality疑. work reportoftenisonepersonday常forpersonhandle affairsstatedegree良goodreflect,excellentpersonoftennot吝attopublicshowfrom己becomefruit,thisalsoisforwhatlike GitHub this样openopen source communitywill存in,andgatherbigquantityexcellentperson才. meanwhile,work reportalsoisonenonvery valuable,letyouhave机will解itsitpersonin做whatfield.',
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
            'content' => 'Q: Iforwhatwantinwork reportprepareupflowertime? A: false设youitselfwork产outis90minute,onegoodwork reportpresentcanwillyouthis 90 minuteworkperfectgroundpresentgive所haveperson,butoneincontentstructure,validinfoquantityandcan读propertyupallnotas best asperson意 PPT maybeonlycanpresentoutyou30minuteworkbecomefruit. andand,ifyouinwork reporttable达andpresent方surface做verynotas best asperson意,nothavebody现outstructure化thinkmethod,nothave SMART notcomeplan,thatalso,youinday常departmentwill议middlecommunication and collaborationascan力,workmiddlelogicthinkingcan力,fromImanageandplancan力alsowillhighlyquality疑. work reportoftenisonepersonday常forpersonhandle affairsstatedegree良goodreflect,excellentpersonoftennot吝attopublicshowfrom己becomefruit,thisalsoisforwhatlike GitHub this样openopen source communitywill存in,andgatherbigquantityexcellentperson才. meanwhile,work reportalsoisonenonvery valuable,letyouhave机will解itsitpersonin做whatfield.',
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
            'content' => 'Q: Iforwhatwantinwork reportprepareupflowertime? A: false设youitselfwork产outis90minute,onegoodwork reportpresentcanwillyouthis 90 minuteworkperfectgroundpresentgive所haveperson,butoneincontentstructure,validinfoquantityandcan读propertyupallnotas best asperson意 PPT maybeonlycanpresentoutyou30minuteworkbecomefruit. andand,ifyouinwork reporttable达andpresent方surface做verynotas best asperson意,nothavebody现outstructure化thinkmethod,nothave SMART notcomeplan,thatalso,youinday常departmentwill议middlecommunication and collaborationascan力,workmiddlelogicthinkingcan力,fromImanageandplancan力alsowillhighlyquality疑. work reportoftenisonepersonday常forpersonhandle affairsstatedegree良goodreflect,excellentpersonoftennot吝attopublicshowfrom己becomefruit,thisalsoisforwhatlike GitHub this样openopen source communitywill存in,andgatherbigquantityexcellentperson才. meanwhile,work reportalsoisonenonvery valuable,letyouhave机will解itsitpersonin做whatfield.',
            'business_id' => '',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
