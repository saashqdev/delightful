<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\Start\V1;

use App\Application\Flow\ExecuteManager\Attachment\AbstractAttachment;
use App\Application\Flow\ExecuteManager\Attachment\AttachmentUtil;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\Memory\LLMMemoryMessage;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunner;
use App\Domain\Agent\Service\DelightfulAgentDomainService;
use App\Domain\Chat\DTO\Message\ChatMessage\VoiceMessage;
use App\Domain\Chat\DTO\Message\TextContentInterface;
use App\Domain\Chat\Entity\DelightfulMessageEntity;
use App\Domain\Chat\Repository\Facade\DelightfulMessageRepositoryInterface;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\DelightfulFlowMessage;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\Branch;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\Structure\TriggerType;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Start\V1\StartNodeParamsConfig;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Dag\VertexResult;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Carbon\Carbon;
use Hyperf\Context\ApplicationContext;
use Hyperf\Odin\Message\Role;
use Throwable;

abstract class AbstractStartNodeRunner extends NodeRunner
{
    protected function chatMessage(VertexResult $vertexResult, ExecutionData $executionData, ?Branch $triggerBranch = null): array
    {
        if ($triggerBranch) {
            $vertexResult->setChildrenIds($triggerBranch->getNextNodes());
        }

        $result = $this->getChatMessageResult($executionData);

        // content or者 files meanwhileforempty
        if ($result['message_content'] === '' && empty($executionData->getTriggerData()->getAttachments())) {
            ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.node.start.content_empty');
        }

        $LLMMemoryMessage = new LLMMemoryMessage(Role::User, $result['message_content'], $executionData->getTriggerData()->getMessageEntity()->getDelightfulMessageId());
        $LLMMemoryMessage->setConversationId($executionData->getConversationId());
        $LLMMemoryMessage->setAttachments($executionData->getTriggerData()->getAttachments());
        $LLMMemoryMessage->setOriginalContent(
            DelightfulFlowMessage::createContent(
                message: $executionData->getTriggerData()->getMessageEntity()->getContent(),
                attachments: $executionData->getTriggerData()->getAttachments(),
            ),
        );
        $LLMMemoryMessage->setTopicId($executionData->getTopicIdString());
        $LLMMemoryMessage->setRequestId($executionData->getId());
        $LLMMemoryMessage->setUid($executionData->getOperator()->getUid());
        $this->flowMemoryManager->receive(
            memoryType: $this->getMemoryType($executionData),
            LLMMemoryMessage: $LLMMemoryMessage,
            nodeDebug: $this->isNodeDebug($executionData),
        );
        return $result;
    }

    protected function openChatWindow(VertexResult $vertexResult, ExecutionData $executionData, Branch $triggerBranch): array
    {
        $vertexResult->clearChildren();
        $userEntity = $executionData->getTriggerData()->getUserEntity();
        $accountEntity = $executionData->getTriggerData()->getAccountEntity();
        $openChatTime = $executionData->getTriggerData()->getTriggerTime();

        $result = [
            'conversation_id' => $executionData->getConversationId(),
            'topic_id' => $executionData->getTopicIdString(),
            'organization_code' => $executionData->getDataIsolation()->getCurrentOrganizationCode(),
            'user' => [
                'id' => $userEntity->getUserId(),
                'nickname' => $userEntity->getNickname(),
                'real_name' => $accountEntity?->getRealName() ?? '',
                'work_number' => $executionData->getTriggerData()->getUserExtInfo()->getWorkNumber(),
                'position' => $executionData->getTriggerData()->getUserExtInfo()->getPosition(),
                'departments' => $executionData->getTriggerData()->getUserExtInfo()->getDepartments(),
            ],
            'open_time' => $openChatTime->format('Y-m-d H:i:s'),
        ];

        // getuptimeopen触hairtime
        $key = 'open_chat_notice_' . $executionData->getConversationId();
        $lastNoticeTime = $this->cache->get($key);

        // ifnothaveuptime,or者distanceuptimetimesecondalready经超pass,that么thenneedexecute
        $config = $triggerBranch->getConfig();
        $intervalSeconds = $this->getIntervalSeconds($config['interval'] ?? 0, $config['unit'] ?? '');
        if (! $lastNoticeTime || (Carbon::make($openChatTime)->diffInSeconds(Carbon::make($lastNoticeTime)) > $intervalSeconds)) {
            $vertexResult->setChildrenIds($triggerBranch->getNextNodes());
            $this->cache->set($key, Carbon::now()->toDateTimeString(), $intervalSeconds);
        }
        return $result;
    }

    protected function addFriend(VertexResult $vertexResult, ExecutionData $executionData, Branch $triggerBranch): array
    {
        $vertexResult->setChildrenIds($triggerBranch->getNextNodes());

        $userEntity = $executionData->getTriggerData()->getUserEntity();
        $accountEntity = $executionData->getTriggerData()->getAccountEntity();
        return [
            'user' => [
                'id' => $userEntity->getUserId(),
                'nickname' => $userEntity->getNickname(),
                'real_name' => $accountEntity?->getRealName() ?? '',
                'work_number' => $executionData->getTriggerData()->getUserExtInfo()->getWorkNumber(),
                'position' => $executionData->getTriggerData()->getUserExtInfo()->getPosition(),
                'departments' => $executionData->getTriggerData()->getUserExtInfo()->getDepartments(),
            ],
            'add_time' => $executionData->getTriggerData()->getTriggerTime()->format('Y-m-d H:i:s'),
        ];
    }

    protected function paramCall(VertexResult $vertexResult, ExecutionData $executionData, Branch $triggerBranch): array
    {
        $vertexResult->setChildrenIds($triggerBranch->getNextNodes());

        $result = [];
        $outputForm = $triggerBranch->getOutput()?->getFormComponent()?->getForm();
        if ($outputForm) {
            $appendConstValue = $executionData->getTriggerData()->getParams();
            foreach ($outputForm->getProperties() ?? [] as $key => $property) {
                if ($property->getType()->isComplex()) {
                    $value = $appendConstValue[$key] ?? [];
                    if (is_string($value)) {
                        // tryonetime json_decode
                        $value = json_decode($value, true);
                    }
                    if (! is_array($value)) {
                        ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, "[{$key}] is not {$property->getType()->value}");
                    }
                    $appendConstValue[$key] = $value;
                }
            }
            $outputForm->appendConstValue($appendConstValue);
            $result = $outputForm->getKeyValue(check: true);
        }

        // increasesystemoutput
        $systemOutputResult = $this->getChatMessageResult($executionData);
        $executionData->saveNodeContext($this->node->getSystemNodeId(), $systemOutputResult);
        $vertexResult->addDebugLog('system_response', $executionData->getNodeContext($this->node->getSystemNodeId()));

        // increasecustomizesystemoutput
        $customSystemOutput = $triggerBranch->getCustomSystemOutput()?->getFormComponent()?->getForm();
        if ($customSystemOutput) {
            $customSystemOutput->appendConstValue($executionData->getTriggerData()->getSystemParams());
            $customSystemOutputResult = $customSystemOutput->getKeyValue(check: true);
            $executionData->saveNodeContext($this->node->getCustomSystemNodeId(), $customSystemOutputResult);
        }
        $vertexResult->addDebugLog('custom_system_response', $executionData->getNodeContext($this->node->getCustomSystemNodeId()));

        return $result;
    }

    protected function routine(VertexResult $vertexResult, ExecutionData $executionData, StartNodeParamsConfig $startNodeParamsConfig): array
    {
        // schedule入参,allbyoutside部call,judgeis哪branch
        $branchId = $executionData->getTriggerData()->getParams()['branch_id'] ?? '';
        if (empty($branchId)) {
            // nothave找toanybranch,directly运line
            $vertexResult->setChildrenIds([]);
            return [];
        }
        $triggerBranch = $startNodeParamsConfig->getBranches()[$branchId] ?? null;
        if (! $triggerBranch) {
            $vertexResult->setChildrenIds([]);
            return [];
        }
        $vertexResult->setChildrenIds($triggerBranch->getNextNodes());
        return $executionData->getTriggerData()->getParams();
    }

    protected function getIntervalSeconds(int $interval, string $unit): int
    {
        return match ($unit) {
            'minutes', 'minute' => $interval * 60,
            'hours', 'hour' => $interval * 3600,
            'seconds', 'second' => $interval,
            default => ExceptionBuilder::throw(FlowErrorCode::ExecuteValidateFailed, 'flow.node.start.unsupported_unit', ['unit' => $unit]),
        };
    }

    private function getChatMessageResult(ExecutionData $executionData): array
    {
        // Process into current parameter format
        $userEntity = $executionData->getTriggerData()->getUserEntity();
        $accountEntity = $executionData->getTriggerData()->getAccountEntity();
        $messageEntity = $executionData->getTriggerData()->getMessageEntity();

        // Process attachments
        $this->appendAttachments($executionData, $messageEntity);

        // Process flow instructions
        $this->appendInstructions($executionData, $messageEntity);

        $content = '';
        if (in_array($executionData->getTriggerType(), [TriggerType::ChatMessage, TriggerType::WaitMessage, TriggerType::ParamCall])) {
            $messageContent = $messageEntity->getContent();
            if ($messageContent instanceof TextContentInterface) {
                $content = $this->getTextContentWithTiming($messageContent, $messageEntity, $executionData);
            }
            $content = trim($content);
            if ($content === '' && ! empty($messageContent->toArray()) && $executionData->getTriggerType() === TriggerType::ChatMessage) {
                $content = json_encode($messageContent->toArray(), JSON_UNESCAPED_UNICODE);
                simple_logger('StartNodeRunner')->warning('UndefinedMessageTypeToText', $messageEntity->toArray());
            }
        }

        return [
            'conversation_id' => $executionData->getConversationId(),
            'topic_id' => $executionData->getTopicIdString(),
            'message_content' => $content,
            'message_type' => $messageEntity->getMessageType()->getName(),
            'message_time' => $executionData->getTriggerData()->getTriggerTime()->format('Y-m-d H:i:s'),
            'organization_code' => $executionData->getDataIsolation()->getCurrentOrganizationCode(),
            'files' => array_map(function (AbstractAttachment $attachment) {
                return $attachment->toStartArray();
            }, $executionData->getTriggerData()->getAttachments()),
            'user' => [
                'id' => $userEntity->getUserId(),
                'nickname' => $userEntity->getNickname(),
                'real_name' => $accountEntity?->getRealName() ?? '',
                'work_number' => $executionData->getTriggerData()->getUserExtInfo()->getWorkNumber(),
                'position' => $executionData->getTriggerData()->getUserExtInfo()->getPosition(),
                'departments' => $executionData->getTriggerData()->getUserExtInfo()->getDepartments(),
            ],
            'agent_key' => $executionData->getTriggerData()->getAgentKey(),
        ];
    }

    private function appendAttachments(ExecutionData $executionData, DelightfulMessageEntity $messageEntity): void
    {
        if (! empty($executionData->getTriggerData()->getAttachments())) {
            return;
        }
        $attachments = AttachmentUtil::getByDelightfulMessageEntity($messageEntity);
        foreach ($attachments as $attachment) {
            $executionData->getTriggerData()->addAttachment($attachment);
        }
    }

    private function appendInstructions(ExecutionData $executionData, DelightfulMessageEntity $messageEntity): void
    {
        $delightfulFlowEntity = $executionData->getDelightfulFlowEntity();
        if (! $delightfulFlowEntity || ! $delightfulFlowEntity->getType()->isMain()) {
            return;
        }
        // 兜bottom,ifnothave agent processfinger令,try实o clockget
        if (empty($executionData->getInstructionConfigs())) {
            $instructs = di(DelightfulAgentDomainService::class)->getAgentById($executionData->getAgentId())->getInstructs();
            $executionData->setInstructionConfigs($instructs);
        }

        // getcurrentmessagebodyfinger令value
        $messageChatInstructions = $messageEntity->getChatInstructions();
        $messageChatInstructionIdMaps = [];
        $messageChatInstructionNameMaps = [];
        foreach ($messageChatInstructions as $messageChatInstruction) {
            if ($messageChatInstruction->getInstruction()->getId()) {
                $messageChatInstructionIdMaps[$messageChatInstruction->getInstruction()->getId()] = $messageChatInstruction;
            }
            if ($messageChatInstruction->getInstruction()->getName()) {
                $messageChatInstructionNameMaps[$messageChatInstruction->getInstruction()->getName()] = $messageChatInstruction;
            }
        }

        $instructions = [];
        // only放current agent configurationprocessfinger令
        foreach ($executionData->getInstructionConfigs() as $instructionConfig) {
            if (! $instructionConfig->isFlowInstructionType()) {
                continue;
            }

            // pass id find
            $messageChatInstruction = $messageChatInstructionIdMaps[$instructionConfig->getId()] ?? null;
            if (! $messageChatInstruction) {
                // pass name find
                $messageChatInstruction = $messageChatInstructionNameMaps[$instructionConfig->getName()] ?? null;
            }

            if ($messageChatInstruction) {
                $value = $messageChatInstruction->getValue();
            } else {
                // ifmessagebodymiddlenothavefinger令value,usedefaultvalue
                $value = $instructionConfig->getDefaultValue();
            }
            $instructions[$instructionConfig->getId()] = $instructionConfig->getNameAndValueByType($value);
        }

        $executionData->saveNodeContext('instructions', $instructions);
    }

    /**
     * Get text content with timing and update processing for voice messages.
     */
    private function getTextContentWithTiming(TextContentInterface $messageContent, DelightfulMessageEntity $messageEntity, ExecutionData $executionData): string
    {
        // If it's a voice message, perform special processing
        if ($messageContent instanceof VoiceMessage) {
            return $this->handleVoiceMessage($messageContent, $messageEntity, $executionData);
        }

        // For other types of messages, directly call getTextContent
        return $messageContent->getTextContent();
    }

    /**
     * Handle voice messages with timing and update logic.
     */
    private function handleVoiceMessage(VoiceMessage $voiceMessage, DelightfulMessageEntity $messageEntity, ExecutionData $executionData): string
    {
        // Set delightfulMessageId for subsequent updates
        $voiceMessage->setDelightfulMessageId($messageEntity->getDelightfulMessageId());

        // Record start time
        $startTime = microtime(true);

        // Call getTextContent to get voice-to-text content
        $textContent = $voiceMessage->getTextContent();

        // Calculate duration
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // If duration is greater than 1 second, update message content to database
        if ($duration > 1.0) {
            $this->updateVoiceMessageContent($messageEntity->getDelightfulMessageId(), $voiceMessage);
        }

        // Clear audio attachments as they have been converted to text content
        $executionData->getTriggerData()->setAttachments([]);

        return $textContent;
    }

    /**
     * Update voice message content to database.
     */
    private function updateVoiceMessageContent(string $delightfulMessageId, VoiceMessage $voiceMessage): void
    {
        try {
            $container = ApplicationContext::getContainer();
            $messageRepository = $container->get(DelightfulMessageRepositoryInterface::class);

            // will VoiceMessage convertforarrayformatuseatupdate
            $messageContent = $voiceMessage->toArray();

            $messageRepository->updateMessageContent($delightfulMessageId, $messageContent);

            $this->logger->info('Voice message content updated successfully (V1)', [
                'delightful_message_id' => $delightfulMessageId,
                'has_transcription' => $voiceMessage->hasTranscription(),
                'transcription_length' => strlen($voiceMessage->getTranscriptionText() ?? ''),
            ]);
        } catch (Throwable $e) {
            // 静默processupdatefail,notimpactmainprocess
            $this->logger->warning('Failed to update voice message content (V1)', [
                'delightful_message_id' => $delightfulMessageId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
