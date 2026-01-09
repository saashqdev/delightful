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

#[FlowNodeDefine(type: NodeType::IntentRecognition->value, code: NodeType::IntentRecognition->name, name: '意graph识别', paramsConfig: IntentRecognitionNodeParamsConfig::class, version: 'v0', singleDebug: true, needInput: true, needOutput: false)]
class IntentRecognitionNodeRunner extends AbstractLLMNodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        /** @var IntentRecognitionNodeParamsConfig $paramsConfig */
        $paramsConfig = $this->node->getNodeParamsConfig();

        // 意graph
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
                ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'common.empty', ['label' => '意graphname']);
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

        // at least是兜bottombranch
        $vertexResult->setChildrenIds($elseBranch['next_nodes'] ?? []);

        $systemPrompt = $this->createSystemPrompt($intentPrompts);

        // if意graph识别start了自动load记忆，那么need剔exceptcurrentmessage
        $ignoreMessageIds = [];
        if ($paramsConfig->getModelConfig()->isAutoMemory()) {
            $ignoreMessageIds = [$executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId()];
        }

        // load记忆
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
        $hasMatch = (bool) ($data['whether识别'] ?? false);
        if ($hasMatch) {
            $bestIntent = $data['most佳意graph'] ?? '';
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
你是一意graph识别sectionpoint，useatanalyzeuser的意graph，你将得to一shareuserinput的content，帮我analyze出user的意graph和置信degree。
resultneedin限定的意graphrangemiddle。

# 技能 - 意graph识别
将你的responseformat化为 JSON object，format如down：
{
    "whether识别": true,
    "识别failreason": "",
    "most佳意graph": "吃饭",
    "匹配to的意graphhave": [
        {
            "意graph": "吃饭",
            "置信degree": 0.8
        },
        {
            "意graph": "睡觉",
            "置信degree": 0.1
        },
        {
            "意graph": "打游戏",
            "置信degree": 0.1
        }
    ],
    "推导procedure":"",
    "remark":""
}    

# process
1. 你将得to一shareuserinput的content，帮我analyze出user的意graph和置信degree。
2. 推理user的意graph，将推理procedure放to JSON middle的 推导procedure field，解释为什么will得出这些意graph和置信degree。
3. if识别to了意graph，请填写most佳匹配和匹配to的意graph，whether识别为 true，most佳意graph 一定是置信degreemost高的，其middle 匹配to的意graphhave field是according to 置信degree from大to小rowcolumn。
4. ifincurrentrangenothave找to任何意graph，whether识别为 false，请填写识别failreason，most佳匹配和匹配to的意graphallshould是空的。
5. 只willreturn JSON format，notwillagainreturn其他content，if一定needhavereturn，请放toremarkmiddle，回答的content一定能be JSON toolparse。

# 限制
- 意graphrange的format是 '意graph'：'意graphdescription'。其middle意graphdescriptioncan为空。意graph和意graphdescription一定是use '' package裹的data。
- notcan回答其他issue，只能回答意graph识别的issue。

# needanalyze的意graphrange如down
{$content}
MARKDOWN;
    }
}
