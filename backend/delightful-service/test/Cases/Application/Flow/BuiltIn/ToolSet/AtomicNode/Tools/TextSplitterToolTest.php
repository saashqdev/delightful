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
            'user_prompt' => 'Ithinkthissegmenttextsplitonedown:late emperorentrepreneurshipnothalfwhilemiddle道崩殂,todaydaydownthreeminute,Yizhou exhausted,thisindeed critical momentalso.loyal guard ministersnot懈atinside,忠志of士忘bodyatoutsideperson,盖追late emperorof殊encounter,欲报ofat陛downalso.誠宜opensheet聖聽,by光late emperorlegacy virtue,expand志士spirit,not宜妄frommeager,importmetaphor失义,by塞忠諫ofpathalso.
宫middle府middle,俱foronebody,陟罰臧no,not宜異same.ifhaveas奸犯科andfor忠善person,should be paidhave司論itspunishment and reward,byshow陛downplain truth,not宜偏private,makeinsideoutside異methodalso.
侍middle,minister Guo Youzhi,Fei Yi,董允etc,this皆良actual,loyal thoughts,isbylate emperorsimple拔byyour majestydown.愚byfor宫middlematter,thingnosize,悉byconsult,thenback施line,requiredcanremedy deficiencies,havewidely beneficial.
will军to宠,propertyline淑average,晓畅军thing,试useat昔day,late emperor称of曰can,isby众議舉寵for督.愚byfor营middlematter,悉byconsult,requiredcanmakeline阵and睦,advantages and disadvantages.
亲贤臣,远smallperson,thisfirst漢 byprosperousalso;亲smallperson,遠賢臣,thisback汉 by倾颓also.late emperorino clock,eachandminister discussionthisthing,not尝notsigh with regretat桓,灵also.侍middle,尚book,long史,join army,this悉贞良deadsectionministers,wishdowntrust them,then漢室of隆,cancalculatedaywhilependingalso.
臣this布衣,farmingatNanyang,苟allpropertycommandatchaotic times,not求heardreachatlords.late emperornotby臣卑鄙,猥fromwronged,threeconsider ministersatthatched cottagemiddle,咨臣bywhen世matter,byis感激,then promisedlate emperorbygallop.backvalue倾覆,appointedatmoment of defeat,by orderatcrisisbetween,尔cometwotenhaveoneyear矣.
late emperor知臣谨慎,entrusted ministerbybigthingalso.受commandbycome,worry day and night,afraid to entrustnot效,by伤late emperorofclear,故fivemonthcross渡瀘,in-depthnot毛.today南sidealreadyset,soldiersalreadyenough,when奖ratethree军,northern pacificationmiddleoriginal,庶竭駑鈍,攘except奸凶,restore Han dynasty,alsoatoldall.thisministersby报late emperorwhileloyal to陛downof职minutealso.toat斟酌损益,enter盡忠言,then攸of,祎,允of任also.
wishdownentrust臣byeffect of defeating rebels and restoring,not效,thenpunish ministers,by告late emperorof spirit.ifno興德of言,thenresponsibility,祎,允etcofslow,by彰its咎;陛downalso appropriatefrom谋,byconsult good ways,察納雅言,deeply pursuelate emperorlast edict,臣not勝受恩感激.',
        ]);
        $runner->execute($vertexResult, $executionData);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
