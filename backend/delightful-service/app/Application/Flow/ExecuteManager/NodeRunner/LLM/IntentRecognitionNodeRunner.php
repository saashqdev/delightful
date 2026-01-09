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

        // at leastisfallbackbottombranch
        $vertexResult->setChildrenIds($elseBranch['next_nodes'] ?? []);

        $systemPrompt = $this->createSystemPrompt($intentPrompts);

        // if意graphidentifystartfromautoloadmemory,thatwhatneed剔exceptcurrentmessage
        $ignoreMessageIds = [];
        if ($paramsConfig->getModelConfig()->isAutoMemory()) {
            $ignoreMessageIds = [$executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId()];
        }

        // loadmemory
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
            $bestIntent = $data['mostbest wishesgraph'] ?? '';
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
youisone意graphidentifysectionpoint,useatanalyzeuser意graph,youwilltooneshareuserinputcontent,helpIanalyzeoutuser意graphandconfidencedegree.
resultneedinqualifier意graphrangemiddle.

# 技can - 意graphidentify
willyouresponseformat化for JSON object,formatlikedown:
{
    "whetheridentify": true,
    "identifyfailreason": "",
    "mostbest wishesgraph": "eat",
    "matchto意graphhave": [
        {
            "意graph": "eat",
            "confidencedegree": 0.8
        },
        {
            "意graph": "睡觉",
            "confidencedegree": 0.1
        },
        {
            "意graph": "打游戏",
            "confidencedegree": 0.1
        }
    ],
    "deduceprocedure":"",
    "remark":""
}    

# process
1. youwilltooneshareuserinputcontent,helpIanalyzeoutuser意graphandconfidencedegree.
2. inferenceuser意graph,willinferenceprocedureputto JSON middle deduceprocedure field,explainforwhatwilloutthisthese意graphandconfidencedegree.
3. ifidentifyto意graph,pleasefill inmost佳matchandmatchto意graph,whetheridentifyfor true,mostbest wishesgraph onesetisconfidencedegreemosthigh,itsmiddle matchto意graphhave fieldisaccording to confidencedegree frombigtosmallrowcolumn.
4. ifincurrentrangenothavefindtoany意graph,whetheridentifyfor false,pleasefill inidentifyfailreason,most佳matchandmatchto意graphallshouldisempty.
5. onlywillreturn JSON format,notwillagainreturnothercontent,ifonesetneedhavereturn,please releasetoremarkmiddle,return答contentonesetcanbe JSON toolparse.

# limit
- 意graphrangeformatis '意graph':'意graphdescription'.itsmiddle意graphdescriptioncanforempty.意graphand意graphdescriptiononesetisuse '' package裹data.
- notcanreturn答otherissue,onlycanreturnanswer意graphidentifyissue.

# needanalyze意graphrangelikedown
{$content}
MARKDOWN;
    }
}
