<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Flow\BuiltIn\ToolSet\AtomicNode\Tools;

use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunnerFactory;
use App\Domain\Flow\Entity\ValueObject\Node;
use App\Domain\Flow\Entity\ValueObject\NodeOutput;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Dag\VertexResult;
use Connector\Component\ComponentFactory;
use Connector\Component\Structure\StructureType;
use HyperfTest\Cases\Application\Flow\ExecuteManager\ExecuteManagerBaseTest;

/**
 * @internal
 */
class TextSplitterToolTest extends ExecuteManagerBaseTest
{
    public function testRunByLLM()
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
                    "type": "fields",
                    "value": "9527.system_prompt",
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
                    "value": "9527.user_prompt",
                    "name": "",
                    "args": null
                }
            ]
        }
    },
    "model_config": {
        "auto_memory": true,
        "temperature": 0.5,
        "max_record": 10
    },
    "option_tools": [
        {
            "tool_id": "atomic_node_text_splitter",
            "tool_set_id": "atomic_node",
            "async": false,
            "custom_system_input": null
        }
    ]
}
JSON, true));
        $output = new NodeOutput();
        $output->setForm(ComponentFactory::generateTemplate(StructureType::Form));
        $node->setOutput($output);

        $runner = NodeRunnerFactory::make($node);
        $vertexResult = new VertexResult();
        $executionData = $this->createExecutionData();
        $executionData->saveNodeContext('9527', [
            'system_prompt' => 'whenuserinputcontentisthinkwantconducttextsplito clock,call text_splitter toolcomeconducttextsplit',
            'user_prompt' => 'Ithinkthissegmenttextsplitonedown:late emperorentrepreneurshipnothalfwhilemiddle道崩殂,todaydaydownthreeminute,Yizhou exhausted,thisindeed critical momentalso.loyal guard ministersnotslackatinside,loyal ambitionof忘bodyatoutsideperson,trace backlate emperorofdifferentencounter,wish to repayofatmajestydownalso.truly shouldopensheet聖聽,bylightlate emperorlegacy virtue,expand志士spirit,notshould妄frommeager,importmetaphorlose righteousness,byblock loyal adviceofpathalso.
palacemiddle府middle,allforonebody,陟罰臧no,notshould異same.ifhaveastreacherous crimesandfor忠goodperson,should be paidhave司論itspunishment and reward,byshowmajestydownplain truth,notshould偏private,makeinsideoutside異methodalso.
servemiddle,minister Guo Youzhi,Fei Yi,Dong Yunetc,thisall goodactual,loyal thoughts,isbylate emperorsimplepullbyyour majestydown.foolbyforpalacemiddlematter,thingnosize,familiarbyconsult,thenbackapplyline,requiredcanremedy deficiencies,havewidely beneficial.
willarmytofavor,propertylinevirtuousaverage,晓畅armything,testuseatpastday,late emperor称ofsaycan,isby众議舉寵forsupervise.foolbyfor營middlematter,familiarbyconsult,requiredcanmakelinearrayandharmonious,advantages and disadvantages.
亲贤minister,farsmallperson,thisfirstHan byprosperousalso;亲smallperson,遠賢minister,thisbackHan bycollapsealso.late emperorino clock,eachandminister discussionthisthing,nottastenotsigh with regretatHuan,spiritalso.servemiddle,stillbook,longhistory,join army,thisfamiliar贞gooddeadsectionministers,wishdowntrust them,thenHan室ofprosperous,cancalculatedaywhilependingalso.
ministerthiscommoner,farmingatNanyang,ifallpropertycommandatchaotic times,notrequestheardreachatlords.late emperornotbyminister humble,obscenefromwronged,threeconsider ministersatthatched cottagemiddle,咨ministerbywhenworldmatter,byisgrateful,then promisedlate emperorbygallop.backvaluecollapse,appointedatmoment of defeat,by orderatcrisisbetween,youcometwotenhaveoneyearindeed.
late emperorknowing minister prudent,entrusted ministerbybigthingalso.受commandbycome,worry day and night,afraid to entrustnoteffect,byhurtlate emperorofclear,故fivemonthcrosscross Lu river,in-depthnothair.todaysouthsidealreadyset,soldiersalreadyenough,whenawardratethreearmy,northern pacificationmiddleoriginal,庶竭駑鈍,expelexcepttreacherous,restore Han dynasty,alsoatoldall.thisministersbyreportlate emperorwhileloyal tomajestydownofpositionminutealso.toatdeliberate gains and losses,enter盡忠words,thenrelatedof,祎,allowof任also.
wishdownentrustministerbyeffect of defeating rebels and restoring,noteffect,thenpunish ministers,byinformlate emperorof spirit.ifnopromote virtueofwords,thenresponsibility,祎,allowetcofslow,bymanifestitsblame;majestydownalso appropriatefromplan,byconsult good ways,accept sincere advice,deeply pursuelate emperorlast edict,ministernot勝受恩grateful.',
        ]);
        $runner->execute($vertexResult, $executionData);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
