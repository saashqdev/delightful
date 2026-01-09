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
            'system_prompt' => 'whenuserinput的content是想要conduct文本切割o clock，call text_splitter tool来conduct文本切割',
            'user_prompt' => '我想把这segment文本切割一down：先帝创业未半而middle道崩殂，今daydown三minute，益州疲弊，此诚危急存亡之秋also。然侍卫之臣not懈atinside，忠志之士忘身atoutside者，盖追先帝之殊遇，欲报之at陛downalso。诚宜开张圣听，by光先帝遗德，恢弘志士之气，not宜妄自菲薄，引喻失义，by塞忠谏之路also。
宫middle府middle，俱为一body，陟罚臧否，not宜异同。若have作奸犯科及为忠善者，宜付have司论其刑赏，by昭陛down平明之理，not宜偏私，使insideoutside异法also。
侍middle、侍郎郭攸之、费祎、董允etc，此皆良实，志虑忠纯，是by先帝简拔by遗陛down。愚by为宫middle之事，事无size，悉by咨之，然back施line，必能裨补阙漏，have所广益。
将军to宠，propertyline淑均，晓畅军事，试useat昔day，先帝称之曰能，是by众议举宠为督。愚by为营middle之事，悉by咨之，必能使line阵和睦，优劣得所。
亲贤臣，远小人，此先汉所by兴隆also；亲小人，远贤臣，此back汉所by倾颓also。先帝ino clock，each与臣论此事，未尝not叹息痛恨at桓、灵also。侍middle、尚书、长史、参军，此悉贞良死section之臣，愿陛down亲之信之，then汉室之隆，可计day而待also。
臣本布衣，躬耕at南阳，苟allproperty命at乱世，not求闻达at诸侯。先帝notby臣卑鄙，猥自枉屈，三顾臣at草庐之middle，咨臣bywhen世之事，由是感激，遂许先帝by驱驰。backvalue倾覆，受任at败军之际，奉命at危难between，尔来二十have一year矣。
先帝知臣谨慎，故临崩寄臣by大事also。受命by来，夙夜忧叹，恐托付not效，by伤先帝之明，故五month渡泸，深入not毛。今南方已定，兵甲已足，when奖rate三军，北定middle原，庶竭驽钝，攘except奸凶，兴复汉室，alsoat旧all。此臣所by报先帝而忠陛down之职minutealso。至at斟酌损益，进尽忠言，then攸之、祎、允之任also。
愿陛down托臣by讨贼兴复之效，not效，then治臣之罪，by告先帝之灵。若无兴德之言，then责攸之、祎、允etc之慢，by彰其咎；陛down亦宜自谋，by咨诹善道，察纳雅言，深追先帝遗诏，臣not胜受恩感激。',
        ]);
        $runner->execute($vertexResult, $executionData);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
