<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\LLM;

use App\Application\Flow\ExecuteManager\Compressible\CompressibleContent;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\LLM\LLMChatNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use Hyperf\Odin\Agent\Tool\UsedTool;
use Hyperf\Odin\Message\AssistantMessage;
use Hyperf\Odin\Message\UserMessage;
use Hyperf\Odin\Message\UserMessageContent;

#[FlowNodeDefine(type: NodeType::LLM->value, code: NodeType::LLM->name, name: '大modelcall', paramsConfig: LLMChatNodeParamsConfig::class, version: 'v0', singleDebug: true, needInput: false, needOutput: true)]
class LLMChatNodeRunner extends AbstractLLMNodeRunner
{
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        /** @var LLMChatNodeParamsConfig $paramsConfig */
        $paramsConfig = $this->node->getNodeParamsConfig();

        $paramsConfig->getSystemPrompt()->getValue()?->getExpressionValue()?->setIsStringTemplate(true);
        $systemPrompt = (string) $paramsConfig->getSystemPrompt()->getValue()->getResult($executionData->getExpressionFieldData());
        $vertexResult->addDebugLog('system_prompt', $systemPrompt);

        $paramsConfig->getUserPrompt()->getValue()?->getExpressionValue()?->setIsStringTemplate(true);
        $userPrompt = (string) $paramsConfig->getUserPrompt()->getValue()->getResult($executionData->getExpressionFieldData());
        $vertexResult->addDebugLog('user_prompt', $userPrompt);

        // system 中whethercontain content
        $systemHasContent = $this->contentIsInSystemPrompt($executionData);
        // user 中whethercontain content
        $userHasContent = $this->contentIsInUserPrompt($executionData);
        if ($frontResults['force_user_has_content'] ?? false) {
            $userHasContent = true;
        }

        $ignoreMessageIds = [];
        if ($systemHasContent || $userHasContent) {
            $ignoreMessageIds = [$executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId()];
        }

        // load记忆
        $memoryManager = $this->createMemoryManager($executionData, $vertexResult, $paramsConfig->getModelConfig(), $paramsConfig->getMessages(), $ignoreMessageIds);

        $contentMessageId = $executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId();
        $contentMessage = $currentMessage = null;
        // 尝试in记忆中找to content message
        foreach ($memoryManager->getMessages() as $message) {
            if ($message->getIdentifier() === $contentMessageId) {
                $contentMessage = $message;
                break;
            }
        }
        if ($userPrompt !== '') {
            if ($userHasContent) {
                if (! $contentMessage) {
                    $contentMessage = new UserMessage();
                    $contentMessage->setContent($userPrompt);
                    $contentMessage->setIdentifier($contentMessageId);
                    // 仅仅添加attachment
                    $imageUrls = $executionData->getTriggerData()->getAttachmentImageUrls();
                    if ($imageUrls) {
                        // havecontentandhaveattachment，添加文本和imagecontent
                        $contentMessage->addContent(UserMessageContent::text($userPrompt));
                        foreach ($imageUrls as $imageUrl) {
                            $contentMessage->addContent(UserMessageContent::imageUrl($imageUrl));
                        }
                    }
                    $memoryManager->addMessage($contentMessage);
                }
            } else {
                // createonenew，in后续use
                $currentMessage = new UserMessage();
                $currentMessage->setContent($userPrompt);
            }
        }

        $agent = $this->createAgent($executionData, $vertexResult, $paramsConfig, $memoryManager, $systemPrompt);

        $chatCompletionResponse = $agent->chat($currentMessage);
        $reasoningResponseText = $responseText = '';

        if ($choice = $chatCompletionResponse->getFirstChoice()) {
            $choiceMessage = $choice->getMessage();
            if ($choiceMessage instanceof AssistantMessage) {
                $responseText = $choiceMessage->getContent();
                $reasoningResponseText = $choiceMessage->getReasoningContent() ?? '';

                $vertexResult->addDebugLog('reasoning', $reasoningResponseText);
                $vertexResult->addDebugLog('origin_response', $responseText);

                // decompress
                $responseText = CompressibleContent::deCompress($responseText, false);
                $vertexResult->addDebugLog('response', $responseText);
            }
        }

        $vertexResult->addDebugLog('reasoning', $reasoningResponseText);
        $vertexResult->addDebugLog('used_tools', array_map(function (UsedTool $useTool) {
            return $useTool->toArray();
        }, $agent->getUsedTools()));

        $result = [
            'text' => $responseText,
            'use_tools' => array_map(function (UsedTool $useTool) {
                return [
                    'tool_name' => $useTool->getName(),
                    'success' => $useTool->isSuccess(),
                    'error_message' => $useTool->getErrorMessage(),
                    'arguments' => json_encode($useTool->getArguments(), JSON_UNESCAPED_UNICODE),
                    'call_result' => $useTool->getResult(),
                    'elapsed_time' => $useTool->getElapsedTime(),
                ];
            }, $agent->getUsedTools()),
        ];

        $vertexResult->setResult($result);
        $executionData->saveNodeContext($this->node->getNodeId(), $result);
    }
}
