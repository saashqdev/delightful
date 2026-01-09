<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\LLM\V1;

use App\Application\Flow\ExecuteManager\Attachment\AttachmentInterface;
use App\Application\Flow\ExecuteManager\Compressible\CompressibleContent;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\Memory\MultiModal\MultiModalBuilder;
use App\Application\Flow\ExecuteManager\Memory\MultiModal\MultiModalContentFormatter;
use App\Application\Flow\ExecuteManager\NodeRunner\LLM\AbstractLLMNodeRunner;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\LLM\V1\LLMChatNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\DelightfulFlowMessageType;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\ReplyMessage\ReplyMessageNodeParamsConfig;
use App\Domain\Flow\Entity\ValueObject\NodeType;
use App\Domain\Flow\Service\DelightfulFlowMultiModalLogDomainService;
use App\Domain\ModelGateway\Entity\ValueObject\ModelGatewayDataIsolation;
use App\Infrastructure\Core\Collector\ExecuteManager\Annotation\FlowNodeDefine;
use App\Infrastructure\Core\Dag\VertexResult;
use App\Infrastructure\Util\Odin\Agent;
use Delightful\FlowExprEngine\Structure\Expression\ValueType;
use Hyperf\Odin\Agent\Tool\UsedTool;
use Hyperf\Odin\Message\AssistantMessage;
use Hyperf\Odin\Message\UserMessage;
use Hyperf\Odin\Message\UserMessageContent;
use Hyperf\Odin\Tool\Definition\ToolDefinition;

#[FlowNodeDefine(
    type: NodeType::LLM->value,
    code: NodeType::LLM->name,
    name: '大modelcall',
    paramsConfig: LLMChatNodeParamsConfig::class,
    version: 'v1',
    singleDebug: true,
    needInput: false,
    needOutput: true
)]
class LLMChatNodeRunner extends AbstractLLMNodeRunner
{
    /**
     * executeLLMchatsectionpoint.
     *
     * @param VertexResult $vertexResult sectionpointexecuteresult
     * @param ExecutionData $executionData executedata
     * @param array $frontResults front置sectionpointresult
     */
    protected function run(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults): void
    {
        /** @var LLMChatNodeParamsConfig $paramsConfig */
        $paramsConfig = $this->node->getNodeParamsConfig();

        $modelName = $paramsConfig->getModel()->getValue()->getResult($executionData->getExpressionFieldData());
        $orgCode = $executionData->getOperator()->getOrganizationCode();
        $dataIsolation = ModelGatewayDataIsolation::createByOrganizationCodeWithoutSubscription($executionData->getDataIsolation()->getCurrentOrganizationCode(), $executionData->getDataIsolation()->getCurrentUserId());
        $model = $this->modelGatewayMapper->getChatModelProxy($dataIsolation, $modelName);

        // default视觉modelconfigurationthenisfrom己
        if ($paramsConfig->getModelConfig()->getVisionModel() === '') {
            $paramsConfig->getModelConfig()->setVisionModel($modelName);
        }

        // if主动close视觉能力。or者 currentmodelsupport，butischoose别model，alsois相whenat要closecurrentmodel视觉能力
        if (! $paramsConfig->getModelConfig()->isVision() || ($model->getModelOptions()->isMultiModal() && $paramsConfig->getModelConfig()->getVisionModel() !== $modelName)) {
            $model->getModelOptions()->setMultiModal(false);
        }

        [$systemPrompt, $userPrompt] = $this->preparePrompts($vertexResult, $executionData, $paramsConfig);

        $userHasContent = false;
        $ignoreMessageIds = [];
        $checkUserContent = (bool) ($frontResults['check_user_content'] ?? true);
        if ($checkUserContent) {
            // system middlewhethercontain content
            $systemHasContent = $this->contentIsInSystemPrompt($executionData);
            // user middlewhethercontain content
            $userHasContent = $this->contentIsInUserPrompt($executionData);

            if ($systemHasContent || $userHasContent) {
                $ignoreMessageIds = [$executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId()];
            }
        }

        // load记忆
        $memoryManager = $this->createMemoryManager($executionData, $vertexResult, $paramsConfig->getModelConfig(), $paramsConfig->getMessages(), $ignoreMessageIds);

        // onlyfrom动记忆needprocessbydown多模statemessage
        if ($paramsConfig->getModelConfig()->isAutoMemory()) {
            $contentMessageId = $executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId();
            $contentMessage = null;
            // 尝试in记忆middle找to content message
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
                        // 仅仅addattachment
                        $imageUrls = $executionData->getTriggerData()->getAttachmentImageUrls();
                        if ($imageUrls) {
                            // havecontentandhaveattachment，addtextandimagecontent
                            $contentMessage->addContent(UserMessageContent::text($userPrompt));
                            foreach ($imageUrls as $imageUrl) {
                                $contentMessage->addContent(UserMessageContent::imageUrl($imageUrl));
                            }
                        }
                        $contentMessage->setParams([
                            'attachments' => $executionData->getTriggerData()->getAttachments(),
                        ]);
                        $memoryManager->addMessage($contentMessage);
                    }
                } else {
                    // create一new，inback续use
                    $currentMessage = new UserMessage();
                    $currentMessage->setContent($userPrompt);
                    $memoryManager->addMessage($currentMessage);
                }
            }

            // processcurrent多模statemessage - only content need立刻callgoprocess
            /** @var null|UserMessage $contentMessage */
            if ($contentMessage?->hasImageMultiModal() && $paramsConfig->getModelConfig()->isVision()) {
                $currentModel = $model->getModelName();
                $visionModel = $paramsConfig->getModelConfig()->getVisionModel();

                // only currentmodeland视觉modelnot一致，or者 currentmodelnot supported多模state o clock。in视觉modeltoolmiddle，currentmodelequal视觉modelandand具have视觉能力，thennotwill产生死loop
                if ($currentModel !== $visionModel || ! $model->getModelOptions()->isMultiModal()) {
                    $multiModalLoglog = MultiModalBuilder::vision(
                        executionData: $executionData,
                        visionModel: $paramsConfig->getModelConfig()->getVisionModel()
                    );
                    if ($multiModalLoglog) {
                        $contentMessage->setParams([
                            'attachments' => $executionData->getTriggerData()->getAttachments(),
                            'analysis_result' => $multiModalLoglog->getAnalysisResult(),
                        ]);
                    }
                }
            }

            // 永远processcurrentsectionpointhistoryattachmentmessage
            $delightfulMessageIds = [];
            foreach ($memoryManager->getMessages() as $message) {
                $delightfulMessageIds[] = $message->getIdentifier();
            }
            $multiModalLogs = di(DelightfulFlowMultiModalLogDomainService::class)->getByMessageIds($executionData->getDataIsolation(), $delightfulMessageIds, true);
            foreach ($memoryManager->getMessages() as $message) {
                if ($message instanceof UserMessage) {
                    $multiModalLog = $multiModalLogs[$message->getIdentifier()] ?? null;
                    if ($multiModalLog) {
                        $visionResponse = $multiModalLog->getAnalysisResult();
                    } else {
                        $visionResponse = $message->getParams()['analysis_result'] ?? '';
                    }
                    /** @var AttachmentInterface[] $attachments */
                    $attachments = $message->getParams()['attachments'] ?? [];
                    $content = MultiModalContentFormatter::formatAllAttachments(
                        $message->getContent(),
                        $visionResponse,
                        $attachments,
                    );
                    $lastMessage = clone $message;
                    $message->setContent($content);
                    $message->setContents(null);
                    // 重新organization多模state
                    if ($model->getModelOptions()->isMultiModal()) {
                        $message->addContent(UserMessageContent::text($content));
                        // 补充多模state
                        $imageUrls = [];
                        foreach ($lastMessage->getContents() ?? [] as $userContent) {
                            if (! empty($userContent->getImageUrl())) {
                                $imageUrls[] = $userContent->getImageUrl();
                                $message->addContent(UserMessageContent::imageUrl($userContent->getImageUrl()));
                            }
                        }
                        foreach ($attachments as $attachment) {
                            if ($attachment->isImage() && ! in_array($attachment->getUrl(), $imageUrls)) {
                                $message->addContent(UserMessageContent::imageUrl($attachment->getUrl()));
                            }
                        }
                    }
                }
            }
        } else {
            if ($userPrompt !== '') {
                // create一new，inback续use
                $currentMessage = new UserMessage();
                $currentMessage->setContent($userPrompt);
                $memoryManager->addMessage($currentMessage);
            }
        }

        $agent = $this->createAgent($executionData, $vertexResult, $paramsConfig, $memoryManager, $systemPrompt, $model);

        $response = $this->executeAgent($agent, $vertexResult, $executionData);

        $vertexResult->addDebugLog('used_tools', array_map(function (UsedTool $useTool) {
            return $useTool->toArray();
        }, $agent->getUsedTools()));

        $vertexResult->addDebugLog('mcp_tools', array_map(function (ToolDefinition $toolDefinition) {
            return $toolDefinition->toArray();
        }, $agent->getMcpTools()));

        [$reasoningResponseText, $responseText] = $response;

        $result = [
            'response' => $responseText,
            'reasoning' => $reasoningResponseText,
            'tool_calls' => array_map(function (UsedTool $useTool) {
                return [
                    'name' => $useTool->getName(),
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

    private function preparePrompts(VertexResult $vertexResult, ExecutionData $executionData, LLMChatNodeParamsConfig $paramsConfig): array
    {
        $paramsConfig->getSystemPrompt()->getValue()?->getExpressionValue()?->setIsStringTemplate(true);
        $systemPrompt = (string) $paramsConfig->getSystemPrompt()->getValue()->getResult($executionData->getExpressionFieldData());
        $vertexResult->addDebugLog('system_prompt', $systemPrompt);

        $paramsConfig->getUserPrompt()->getValue()?->getExpressionValue()?->setIsStringTemplate(true);
        $userPrompt = (string) $paramsConfig->getUserPrompt()->getValue()->getResult($executionData->getExpressionFieldData());

        return [$systemPrompt, $userPrompt];
    }

    /**
     * execute代理andgetresponse.
     *
     * @param Agent $agent 代理object
     * @param VertexResult $vertexResult sectionpointexecuteresult
     * @param ExecutionData $executionData executedata
     * @return array [推理text, responsetext]
     */
    private function executeAgent(Agent $agent, VertexResult $vertexResult, ExecutionData $executionData): array
    {
        if ($executionData->isStream() && $this->isIntrovertedReplyMessageNode($vertexResult, $executionData)) {
            return $this->handleStreamedResponse($agent, $vertexResult, $executionData);
        }
        return $this->handleNormalResponse($agent, $vertexResult);
    }

    private function handleStreamedResponse(Agent $agent, VertexResult $vertexResult, ExecutionData $executionData): array
    {
        $chatCompletionChoiceGenerator = $agent->chatStreamed();
        // inside敛reply
        $frontResults['chat_completion_choice_generator'] = $chatCompletionChoiceGenerator;
        return $this->tryIntrovertedReplyMessageNode($vertexResult, $executionData, $frontResults);
    }

    private function handleNormalResponse(Agent $agent, VertexResult $vertexResult): array
    {
        $chatCompletionResponse = $agent->chat();
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

        return [$reasoningResponseText, $responseText];
    }

    private function tryIntrovertedReplyMessageNode(VertexResult $vertexResult, ExecutionData $executionData, $frontResults): array
    {
        $nextNodeId = $vertexResult->getChildrenIds()[0];
        $flow = $executionData->getDelightfulFlowEntity();
        $nextNode = $flow?->getNodeById($nextNodeId);
        if ($nextNode?->getNodeType() !== NodeType::ReplyMessage->value) {
            return ['', ''];
        }

        $this->executeNodeIntroverted($vertexResult, $nextNode, $executionData, $frontResults);
        $responseText = $vertexResult->getDebugLog()['llm_stream_response'] ?? '';
        $reasoningResponseText = $vertexResult->getDebugLog()['llm_stream_reasoning_response'] ?? '';
        $executionData->saveNodeContext($this->node->getNodeId(), [
            'response' => $responseText,
            'reasoning' => $reasoningResponseText,
        ]);
        return [$reasoningResponseText, $responseText];
    }

    private function isIntrovertedReplyMessageNode(VertexResult $vertexResult, ExecutionData $executionData): bool
    {
        // only一子sectionpoint
        if (count($vertexResult->getChildrenIds()) !== 1) {
            return false;
        }
        $nextNodeId = $vertexResult->getChildrenIds()[0];

        $flow = $executionData->getDelightfulFlowEntity();

        $nextNode = $flow?->getNodeById($nextNodeId);
        if ($nextNode?->getNodeType() !== NodeType::ReplyMessage->value) {
            return false;
        }
        /** @var ReplyMessageNodeParamsConfig $paramsConfig */
        $paramsConfig = $nextNode->getNodeParamsConfig();
        // 只support text and markdown
        if (! in_array($paramsConfig->getType(), [DelightfulFlowMessageType::Text, DelightfulFlowMessageType::Markdown], true)) {
            return false;
        }

        $contentValue = $paramsConfig->getContent()?->getValue();
        if (! $contentValue) {
            return false;
        }

        // haveandonly一currentsectionpointtable达typequote
        $expressionItems = $contentValue->getAllFieldsExpressionItem() ?? [];
        if (count($expressionItems) !== 1) {
            return false;
        }

        // maybealsohave其他stringsplice，暂o clockalsonotinside敛
        $items = match ($contentValue->getType()) {
            ValueType::Const => $contentValue->getConstValue()?->getItems() ?? [],
            ValueType::Expression => $contentValue->getExpressionValue()?->getItems() ?? [],
        };
        if (count($items) !== 1) {
            return false;
        }

        $expressionItem = $expressionItems[0];
        $currentNodeOutput = ["{$this->node->getNodeId()}.text", "{$this->node->getNodeId()}.response"];
        if (! in_array($expressionItem->getValue(), $currentNodeOutput)) {
            return false;
        }

        return true;
    }
}
