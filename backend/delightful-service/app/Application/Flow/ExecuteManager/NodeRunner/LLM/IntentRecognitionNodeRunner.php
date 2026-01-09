<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\LLM;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\LLM\IntentRecognitionNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\FlowExprEngine\Component;
use Hyperf\Odin\Message\UserMessage;

#[FlowNodeDefine(type: NodeType::IntentRecognition->value, code: NodeType::IntentRecognition->name, name: '意图识别', paramsConfig: IntentRecognitionNodeParamsConfig::class, version: 'v0', singleDebug: true, needInput: true, needOutput: false)]
class IntentRecognitionNodeRunner extends AbstractLLMNodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        /** @var IntentRecognitionNodeParamsConfig $paramsConfig */
        $paramsConfig = $this->node->getNodeParamsConfig();

        // 意图
        $input = $this->node->getInput()?->getForm()?->getForm()?->getKeyValue($executionData->getExpressionFieldData(), true) ?? [];
        $vertexResult->setInput($input);
        $intent = $input['intent'] ?? '';
        if (! is_string($intent) || $intent === '') {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.node.intent.empty');
        }
        $userPrompt = $intent;

        $childrenNodes = [];
        $elseBranch = [];
        $intentPrompts = [];
        foreach ($paramsConfig->getBranches() as $branch) {
            if ($branch['branch_type'] === 'else') {
                $elseBranch = $branch;
                continue;
            }
            /** @var null|Component $titleComponent */
            $titleComponent = $branch['title'] ?? null;
            /** @var null|Component $descComponent */
            $descComponent = $branch['desc'] ?? null;

            $titleComponent?->getValue()?->getExpressionValue()?->setIsStringTemplate(true);
            $descComponent?->getValue()?->getExpressionValue()?->setIsStringTemplate(true);

            $title = $titleComponent?->getValue()?->getResult($executionData->getExpressionFieldData());
            if (! is_string($title) || $title === '') {
                ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'common.empty', ['label' => '意图name']);
            }
            $desc = $descComponent?->getValue()?->getResult($executionData->getExpressionFieldData()) ?? '';
            if (! is_string($desc)) {
                $desc = '';
            }
            $intentPrompts[] = [
                'title' => $title,
                'desc' => $desc,
            ];
            $childrenNodes[$title] = $branch['next_nodes'] ?? [];
        }

        // 至少是兜底分支
        $vertexResult->setChildrenIds($elseBranch['next_nodes'] ?? []);

        $systemPrompt = $this->createSystemPrompt($intentPrompts);

        // 如果意图识别开启了自动加载记忆，那么需要剔除当前message
        $ignoreMessageIds = [];
        if ($paramsConfig->getModelConfig()->isAutoMemory()) {
            $ignoreMessageIds = [$executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId()];
        }

        // 加载记忆
        $messageHistory = $this->createMemoryManager($executionData, $vertexResult, $paramsConfig->getModelConfig(), ignoreMessageIds: $ignoreMessageIds);

        $agent = $this->createAgent($executionData, $vertexResult, $paramsConfig, $messageHistory, $systemPrompt);
        $response = $agent->chat(new UserMessage($userPrompt));
        $responseText = (string) $response;

        $vertexResult->addDebugLog('response', $responseText);
        $data = $this->formatJson($responseText);
        $vertexResult->addDebugLog('response_data', $data);
        if (! $data) {
            return;
        }
        $hasMatch = (bool) ($data['是否识别'] ?? false);
        if ($hasMatch) {
            $bestIntent = $data['最佳意图'] ?? '';
            $vertexResult->setChildrenIds($childrenNodes[$bestIntent] ?? []);
        }
    }

    private function createSystemPrompt(array $intentPrompts): string
    {
        $content = '';
        foreach ($intentPrompts as $prompt) {
            $content .= "- '{$prompt['title']}':'{$prompt['desc']}'\n";
        }

        return <<<MARKDOWN
'# role
你是一个意图识别节点，用于分析user的意图，你将得到一份user输入的content，帮我分析出user的意图和置信度。
result需要在限定的意图范围中。

# 技能 - 意图识别
将你的响应格式化为 JSON object，格式如下：
{
    "是否识别": true,
    "识别fail原因": "",
    "最佳意图": "吃饭",
    "匹配到的意图有": [
        {
            "意图": "吃饭",
            "置信度": 0.8
        },
        {
            "意图": "睡觉",
            "置信度": 0.1
        },
        {
            "意图": "打游戏",
            "置信度": 0.1
        }
    ],
    "推导过程":"",
    "remark":""
}    

# process
1. 你将得到一份user输入的content，帮我分析出user的意图和置信度。
2. 推理user的意图，将推理过程放到 JSON 中的 推导过程 field，解释为什么会得出这些意图和置信度。
3. 如果识别到了意图，请填写最佳匹配和匹配到的意图，是否识别为 true，最佳意图 一定是置信度最高的，其中 匹配到的意图有 field是according to 置信度 从大到小排列。
4. 如果在当前范围没有找到任何意图，是否识别为 false，请填写识别fail原因，最佳匹配和匹配到的意图都应该是空的。
5. 只会return JSON 格式，不会再return其他content，如果一定需要有return，请放到remark中，回答的content一定能被 JSON 工具解析。

# 限制
- 意图范围的格式是 '意图'：'意图description'。其中意图description可以为空。意图和意图description一定是用 '' 包裹的数据。
- 不可以回答其他issue，只能回答意图识别的issue。

# 需要分析的意图范围如下
{$content}
MARKDOWN;
    }
}
