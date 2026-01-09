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
            'system_prompt' => 'whenuserinputcontentis想wantconducttext切割o clock,call text_splitter toolcomeconducttext切割',
            'user_prompt' => 'I想thissegmenttext切割onedown:先帝创业nothalfwhilemiddle道崩殂,今daydownthreeminute,益州疲弊,this诚危急存亡之秋also.然侍卫之臣not懈atinside,忠志之士忘身atoutside者,盖追先帝之殊遇,欲报之at陛downalso.诚宜open张圣听,by光先帝遗德,恢弘志士之气,not宜妄from菲薄,引喻失义,by塞忠谏之路also.
宫middle府middle,俱foronebody,陟罚臧no,not宜异同.若haveas奸犯科andfor忠善者,宜付have司论its刑赏,by昭陛down平明之理,not宜偏私,makeinsideoutside异法also.
侍middle,侍郎郭攸之,费祎,董允etc,this皆良实,志虑忠纯,isby先帝简拔by遗陛down.愚byfor宫middle之事,事nosize,悉by咨之,然back施line,必can裨补阙漏,have所广益.
will军to宠,propertyline淑均,晓畅军事,试useat昔day,先帝称之曰can,isby众议举宠for督.愚byfor营middle之事,悉by咨之,必canmakeline阵and睦,优劣所.
亲贤臣,远smallperson,this先汉所by兴隆also;亲smallperson,远贤臣,thisback汉所by倾颓also.先帝ino clock,eachand臣论this事,not尝not叹息痛恨at桓,灵also.侍middle,尚书,long史,参军,this悉贞良死section之臣,愿陛down亲之信之,then汉室之隆,can计daywhile待also.
臣本布衣,躬耕at南阳,苟allproperty命at乱世,not求闻达at诸侯.先帝notby臣卑鄙,猥from枉屈,three顾臣at草庐之middle,咨臣bywhen世之事,byis感激,遂许先帝by驱驰.backvalue倾覆,受任at败军之际,奉命at危难between,尔cometwotenhaveoneyear矣.
先帝知臣谨慎,故临崩寄臣bybig事also.受命bycome,夙夜忧叹,恐托付not效,by伤先帝之明,故fivemonth渡泸,深入not毛.今南方already定,兵甲already足,when奖ratethree军,北定middle原,庶竭驽钝,攘except奸凶,兴复汉室,alsoatoldall.this臣所by报先帝while忠陛down之职minutealso.toat斟酌损益,enter尽忠言,then攸之,祎,允之任also.
愿陛down托臣by讨贼兴复之效,not效,then治臣之罪,by告先帝之灵.若no兴德之言,then责攸之,祎,允etc之slow,by彰its咎;陛down亦宜from谋,by咨诹善道,察纳雅言,深追先帝遗诏,臣not胜受恩感激.',
        ]);
        $runner->execute($vertexResult, $executionData);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
