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
            'user_prompt' => 'I think this segment text split one down: late emperor entrepreneurship not half while middle way collapsed and died, today day down three minute, Yizhou exhausted, this indeed critical moment also. loyal guard ministers not slack at inside, loyal ambition of forget body at outside person, trace back late emperor of different encounter, wish to repay of at majesty down also. truly should open sheet sacred listening, by light late emperor legacy virtue, expand aspiring scholar spirit, not should recklessly from meager, import metaphor lose righteousness, by block loyal advice of path also.
palace middle office middle, all for one body, reward punishment evaluation no, not should differ same. if have as treacherous crimes and for loyal good person, should be paid have official discussion its punishment and reward, by show majesty down plain truth, not should be partial private, make inside outside differ method also.
serve middle, minister Guo Youzhi, Fei Yi, Dong Yun etc, this all good actual, loyal thoughts, is by late emperor simple pull by your majesty down. fool by for palace middle matter, thing no size, familiar by consult, then back apply line, required can remedy deficiencies, have widely beneficial.
will army to favor, property line virtuous average, understand military thing, test use at past day, late emperor call of say can, is by assemblydiscuss elect favor for supervise. fool by for camp middle matter, familiar by consult, required can make line array and harmonious, advantages and disadvantages.
befriend worthy minister, far small person, this first Han by prosperous also; befriend small person, distance worthy minister, this back Han by collapse also. late emperor in o clock, each and minister discussion this thing, not taste not sigh with regret at Huan, spirit also. serve middle, still book, long history, join army, this familiar virtuous good dead section ministers, wish down trust them, then Han chamber of prosperous, can calculate day while pending also.
minister this commoner, farming at Nanyang, if all property command at chaotic times, not request heard reach at lords. late emperor not by minister humble, obscene from wronged, three consider ministers at thatched cottage middle, consult minister by when world matter, by is grateful, then promised late emperor by gallop. back value collapse, appointed at moment of defeat, by order at crisis between, you come two ten have one year indeed.
late emperor knowing minister prudent, entrusted minister by big thing also. receive command by come, worry day and night, afraid to entrust not effect, by hurt late emperor of clear, therefore five month cross cross Lu river, in-depth not hair. today south side already set, soldiers already enough, when award rate three army, northern pacification middle original, humbly exhaust dull stupid, expel except treacherous, restore Han dynasty, also at old all. this ministers by report late emperor while loyal to majesty down of position minute also. to at deliberate gains and losses, enter exhaust loyal words, then related of, Wei Yi, allow of responsibility also.
wish down entrust minister by effect of defeating rebels and restoring, not effect, then punish ministers, by inform late emperor of spirit. if no promote virtue of words, then responsibility, Wei Yi, allow etc of slow, by manifest its blame; majesty down also appropriate from plan, by consult good ways, accept sincere advice, deeply pursue late emperor last edict, minister not victory receive grace grateful.',
        ]);
        $runner->execute($vertexResult, $executionData);

        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
