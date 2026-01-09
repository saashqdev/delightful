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

#[FlowNodeDefine(type: NodeType::IntentRecognition->value, code: NodeType::IntentRecognition->name, name: '意graphidentify', paramsConfig: IntentRecognitionNodeParamsConfig::class, version: 'v0', singleDebug: true, needInput: true, needOutput: false)]
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

        // at leastis兜bottombranch
        $vertexResult->setChildrenIds($elseBranch['next_nodes'] ?? []);

        $systemPrompt = $this->createSystemPrompt($intentPrompts);

        // if意graphidentifystartfrom动load记忆，that么need剔exceptcurrentmessage
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
        $hasMatch = (bool) ($data['whetheridentify'] ?? false);
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
youisone意graphidentifysectionpoint，useatanalyzeuser意graph，youwilltooneshareuserinputcontent，帮Ianalyzeoutuser意graphand置信degree。
resultneedin限定意graphrangemiddle。

# 技can - 意graphidentify
willyouresponseformat化for JSON object，format如down：
{
    "whetheridentify": true,
    "identifyfailreason": "",
    "most佳意graph": "吃饭",
    "matchto意graphhave": [
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
1. youwilltooneshareuserinputcontent，帮Ianalyzeoutuser意graphand置信degree。
2. 推理user意graph，will推理procedure放to JSON middle 推导procedure field，解释for什么willoutthisthese意graphand置信degree。
3. ifidentifyto意graph，请填写most佳matchandmatchto意graph，whetheridentifyfor true，most佳意graph one定is置信degreemost高，itsmiddle matchto意graphhave fieldisaccording to 置信degree from大to小rowcolumn。
4. ifincurrentrangenothave找toany意graph，whetheridentifyfor false，请填写identifyfailreason，most佳matchandmatchto意graphallshouldisempty。
5. 只willreturn JSON format，notwillagainreturnothercontent，ifone定needhavereturn，请放toremarkmiddle，return答contentone定canbe JSON toolparse。

# limit
- 意graphrangeformatis '意graph'：'意graphdescription'。itsmiddle意graphdescriptioncanforempty。意graphand意graphdescriptionone定isuse '' package裹data。
- notcanreturn答otherissue，只canreturn答意graphidentifyissue。

# needanalyze意graphrange如down
{$content}
MARKDOWN;
    }
}
