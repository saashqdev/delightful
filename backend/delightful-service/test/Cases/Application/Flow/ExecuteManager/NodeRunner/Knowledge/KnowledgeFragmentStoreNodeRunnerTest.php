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
            'content' => 'Q: howmaketogoodreport? A: canread [work reportthinking导graphv1.1.2](https://xxxxx.com/docx/605565507182194688) orcontinuereaddowntext; immediately,Ipassreturnanswer whichtheseissuecan达become「sayclearImakewhat,makehow样」 issueclearsingle (youth version) 1. ingoyear/upseasondegree,youmainresponsibleor参andproject,ineach阶segmentoriginally scheduledplanishow?thistheseplanmiddleyou responsibledepartmentminuteallbyo clockcomplete?finalactualfallgroundsituationhow? a. ifprojectcompleteverygood,thisgoodresultandyou makewhichtheseefforthaveclose? b. ifprojectcompletenotgood,existsinissueandyou反思iswhat?youishowimprovement?improvementresulthow? 2. passgooneyearwithin,youallforyouteammakewhatthing?付outwhat?thisthesethingand付outresultishow?goodinwhichwithin?notgoodiswhatreason?forwhat? 3. passgooneyearwithin,youallforyoufromselfallmakewhatthing?付outwhat?thisthesethingand付outresultishow?goodinwhichwithin?notgoodiswhatreason?forwhat? 4. 明yearuphalfyearplaniswhat?',
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
            'content' => 'Q: Iforwhatwantinwork reportprepareupflowertime? A: falsesetyouitselfwork产outis90minute,onegoodwork reportpresentcanwillyouthis 90 minuteworkperfectgroundpresentgive haveperson,butoneincontentstructure,validinfoquantityandcanreadpropertyupallnotas best aspersonintention PPT maybeonlycanpresentoutyou30minuteworkbecomefruit. andand,ifyouinwork reporttable达andpresentsidesurfacemakeverynotas best aspersonintention,nothavebodyshowoutstructure化thinkmethod,nothave SMART notcomeplan,thatalso,youinday常departmentwill议middlecommunication and collaborationascancapability,workmiddlelogicthinkingcancapability,fromImanageandplancancapabilityalsowillhighlyquality疑. work reportoftenisonepersonday常forpersonhandle affairsstatedegree良goodreflect,excellentpersonoftennot吝attopublicshowfromselfbecomefruit,thisalsoisforwhatlike GitHub this样openopen source communitywillexistsin,andgatherbigquantityexcellentpersononly. meanwhile,work reportalsoisonenonvery valuable,letyouhavemachinewill解itsitpersoninmakewhatfield.',
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
            'content' => 'Q: Iforwhatwantinwork reportprepareupflowertime? A: falsesetyouitselfwork产outis90minute,onegoodwork reportpresentcanwillyouthis 90 minuteworkperfectgroundpresentgive haveperson,butoneincontentstructure,validinfoquantityandcanreadpropertyupallnotas best aspersonintention PPT maybeonlycanpresentoutyou30minuteworkbecomefruit. andand,ifyouinwork reporttable达andpresentsidesurfacemakeverynotas best aspersonintention,nothavebodyshowoutstructure化thinkmethod,nothave SMART notcomeplan,thatalso,youinday常departmentwill议middlecommunication and collaborationascancapability,workmiddlelogicthinkingcancapability,fromImanageandplancancapabilityalsowillhighlyquality疑. work reportoftenisonepersonday常forpersonhandle affairsstatedegree良goodreflect,excellentpersonoftennot吝attopublicshowfromselfbecomefruit,thisalsoisforwhatlike GitHub this样openopen source communitywillexistsin,andgatherbigquantityexcellentpersononly. meanwhile,work reportalsoisonenonvery valuable,letyouhavemachinewill解itsitpersoninmakewhatfield.',
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
            'content' => 'Q: Iforwhatwantinwork reportprepareupflowertime? A: falsesetyouitselfwork产outis90minute,onegoodwork reportpresentcanwillyouthis 90 minuteworkperfectgroundpresentgive haveperson,butoneincontentstructure,validinfoquantityandcanreadpropertyupallnotas best aspersonintention PPT maybeonlycanpresentoutyou30minuteworkbecomefruit. andand,ifyouinwork reporttable达andpresentsidesurfacemakeverynotas best aspersonintention,nothavebodyshowoutstructure化thinkmethod,nothave SMART notcomeplan,thatalso,youinday常departmentwill议middlecommunication and collaborationascancapability,workmiddlelogicthinkingcancapability,fromImanageandplancancapabilityalsowillhighlyquality疑. work reportoftenisonepersonday常forpersonhandle affairsstatedegree良goodreflect,excellentpersonoftennot吝attopublicshowfromselfbecomefruit,thisalsoisforwhatlike GitHub this样openopen source communitywillexistsin,andgatherbigquantityexcellentpersononly. meanwhile,work reportalsoisonenonvery valuable,letyouhavemachinewill解itsitpersoninmakewhatfield.',
            'business_id' => '',
        ]);
        $runner->execute($vertexResult, $executionData, []);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
