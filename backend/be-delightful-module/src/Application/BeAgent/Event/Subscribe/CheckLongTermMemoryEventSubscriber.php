<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Event\Subscribe;

use App\Application\Chat\Service\DelightfulChatMessageAppService;
use App\Application\LongTermMemory\DTO\EvaluateConversationRequestDTO;
use App\Application\LongTermMemory\Enum\AppCodeEnum;
use App\Application\LongTermMemory\Service\LongTermMemoryAppService as DelightfulServiceLongTermMemoryAppService;
use App\Application\ModelGateway\Service\ModelConfigAppService;
use App\Domain\Chat\Entity\ValueObject\LLMModelEnum;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Domain\BeAgent\Event\CheckLongTermMemoryEvent;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Long-term memory check event subscriber.
 * Responsible for handling the specific logic of long-term memory check events.
 */
class CheckLongTermMemoryEventSubscriber implements ListenerInterface
{
    /**
     * Listen to events.
     *
     * @return array Array of event classes to listen to
     */
    public function listen(): array
    {
        return [
            CheckLongTermMemoryEvent::class,
        ];
    }

    /**
     * Process the event.
     *
     * @param object $event Event object
     */
    public function process(object $event): void
    {
        // Type check
        if (! $event instanceof CheckLongTermMemoryEvent) {
            return;
        }

        try {
            $this->getLogger()->info('Starting long-term memory check event processing', [
                'event_id' => $event->getEventId(),
                'organization_code' => $event->getOrganizationCode(),
                'user_id' => $event->getUserId(),
                'conversation_id' => $event->getConversationId(),
                'chat_topic_id' => $event->getChatTopicId(),
                'prompt_length' => mb_strlen($event->getPrompt()),
                'has_attachments' => ! empty($event->getAttachments()),
                'instructions_count' => count($event->getInstructions()),
            ]);

            // Get conversationId directly from event
            $conversationId = $event->getConversationId();
            if (empty($conversationId)) {
                $this->getLogger()->warning('conversation_id in event is empty', [
                    'chat_topic_id' => $event->getChatTopicId(),
                    'event_id' => $event->getEventId(),
                ]);
                return;
            }

            // Build authorization object
            $authorization = $this->createUserAuthorization($event->getOrganizationCode(), $event->getUserId());

            // Get history messages
            $historyMessages = $this->getConversationHistory($authorization, $conversationId, $event->getChatTopicId());
            // Build complete conversation content
            $conversationContent = $this->buildConversationContentWithHistory($event, $historyMessages);

            // Get model name through fallback chain
            $modelName = di(ModelConfigAppService::class)->getChatModelTypeByFallbackChain(
                $event->getOrganizationCode(),
                LLMModelEnum::DEEPSEEK_V3->value
            );

            // Create evaluation request DTO
            $dto = new EvaluateConversationRequestDTO([
                'modelName' => $modelName,
                'conversationContent' => $conversationContent,
                'appId' => AppCodeEnum::BE_DELIGHTFUL->value,
            ]);
            // Call delightful-service long-term memory evaluation service
            $this->getLongTermMemoryApp()->evaluateAndCreateMemory($dto, $authorization);
        } catch (Throwable $e) {
            $this->getLogger()->error('Exception occurred while processing long-term memory check event', [
                'event_id' => $event->getEventId(),
                'error' => $e->getMessage(),
                'organization_code' => $event->getOrganizationCode(),
                'user_id' => $event->getUserId(),
                'conversation_id' => $event->getConversationId(),
                'chat_topic_id' => $event->getChatTopicId(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Get long-term memory application service.
     */
    private function getLongTermMemoryApp(): DelightfulServiceLongTermMemoryAppService
    {
        return \Hyperf\Support\make(DelightfulServiceLongTermMemoryAppService::class);
    }

    /**
     * Get chat message application service.
     */
    private function getDelightfulChatMessageApp(): DelightfulChatMessageAppService
    {
        return \Hyperf\Support\make(DelightfulChatMessageAppService::class);
    }

    /**
     * Get logger.
     */
    private function getLogger(): LoggerInterface
    {
        return \Hyperf\Support\make(LoggerFactory::class)->get(static::class);
    }

    /**
     * Create user authorization object.
     */
    private function createUserAuthorization(string $organizationCode, string $userId): DelightfulUserAuthorization
    {
        $authorization = new DelightfulUserAuthorization();
        $authorization->setId($userId);
        $authorization->setOrganizationCode($organizationCode);
        $authorization->setApplicationCode(AppCodeEnum::BE_DELIGHTFUL->value);
        return $authorization;
    }

    /**
     * Get conversation history messages.
     */
    private function getConversationHistory(DelightfulUserAuthorization $authorization, string $conversationId, string $topicId): array
    {
        try {
            return $this->getDelightfulChatMessageApp()->getConversationChatCompletionsHistory(
                $authorization,
                $conversationId,
                50, // Get the most recent 50 messages
                $topicId,
                false // Use traditional role format (user/assistant) instead of user nicknames
            );
        } catch (Throwable $e) {
            $this->getLogger()->error('Failed to get conversation history messages', [
                'conversation_id' => $conversationId,
                'topic_id' => $topicId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Build conversation content with history messages.
     */
    private function buildConversationContentWithHistory(CheckLongTermMemoryEvent $event, array $historyMessages): string
    {
        $content = [];
        // Add history messages
        if (! empty($historyMessages)) {
            $content[] = '=== History Conversation ===';
            foreach ($historyMessages as $message) {
                if (is_array($message) && isset($message['role'], $message['content'])) {
                    $content[] = $message['role'] . ': ' . $message['content'];
                }
            }
            $content[] = "=== End of History Conversation ===\n";
        }
        // Add current user message
        $content[] = '=== Current Message ===';
        $content[] = "User message: {$event->getPrompt()}";

        // Add mention information (if any)
        if (! empty($event->getMentions())) {
            $mentionsData = json_decode($event->getMentions(), true);
            if (is_array($mentionsData) && ! empty($mentionsData)) {
                $content[] = 'Mentions: ' . json_encode($mentionsData, JSON_UNESCAPED_UNICODE);
            }
        }
        return implode("\n", $content);
    }
}
