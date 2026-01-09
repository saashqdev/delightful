<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Application\Flow\BuiltIn\ToolSet\Crontab\Tools;

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
class UserCrontabToolTest extends ExecuteManagerBaseTest
{
    public function testCreateUserTaskTool()
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
            "tool_id": "crontab_create_user_crontab",
            "tool_set_id": "crontab",
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
        // $executionData->setTopicId('750436587206451201');
        // $executionData->setConversationId('728277721403252736');
        // $executionData->setAgentId('725682656757252096');
        $currentDateTime = date('Y-m-d H:i:s');
        $executionData->saveNodeContext('9527', [
            'system_prompt' => <<<MARKDOWN
# role
你是一个can帮助user快速createuser级别scheduletask的助手


## process
1、call `create_user_crontab` 工具createuser级别scheduletask
2、currenttime是:{$currentDateTime}
-topic_id是：750436587206451201
-agent_id是：725682656757252096
​​3、你needcheckday+time  是否比currenttime大，如果不大，needreminderusertime只能是未来的time
4、你need保证user输入的hint词中，有day, time和name的value

# 上下文


user的昵称是：currentuser的昵称


MARKDOWN,

            // 'user_prompt' => '帮我create一个scheduletask，taskname：reminder我做饭，从明天开始，每天早上9点execute，显示一条reminder我做饭的message',
            'user_prompt' => '帮我create一个scheduletask，taskname：reminder我做饭，明天10点reminder我，显示一条reminder我做饭的message',
        ]);

        $runner->execute($vertexResult, $executionData);
        // 打印vertexResult
        // var_dump($vertexResult);
        $this->assertTrue($node->getNodeDebugResult()->isSuccess());
    }
}
