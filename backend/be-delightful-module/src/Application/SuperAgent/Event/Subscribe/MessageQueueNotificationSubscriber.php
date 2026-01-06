<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe;

use App\Domain\Chat\Entity\ValueObject\SocketEventType;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Infrastructure\Util\SocketIO\SocketIOUtil;
use Delightful\AsyncEvent\Kernel\Annotation\AsyncListener;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\MessageQueueEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\TopicEntity;
use Delightful\BeDelightful\Domain\SuperAgent\Event\MessageQueueConsumedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\MessageQueueCreatedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\MessageQueueDeletedEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Event\MessageQueueUpdatedEvent;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Message Queue Notification Subscriber.
 * Handles message queue events and sends WebSocket notifications to clients.
 */
#[AsyncListener]
#[Listener]
class MessageQueueNotificationSubscriber implements ListenerInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private readonly DelightfulUserDomainService $userDomainService,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(static::class);
    }

    /**
     * List of events to listen to.
     */
    public function listen(): array
    {
        return [
            MessageQueueCreatedEvent::class,
            MessageQueueUpdatedEvent::class,
            MessageQueueDeletedEvent::class,
            MessageQueueConsumedEvent::class,
        ];
    }

    /**
     * Process event.
     */
    public function process(object $event): void
    {
        // Log received event
        $this->logger->info('MessageQueueNotificationSubscriber received event', [
            'event_class' => get_class($event),
        ]);

        try {
            $this->pushMessageQueueNotification(
                $event->getTopicEntity(),
                $event->getMessageQueueEntity()
            );
        } catch (Throwable $e) {
            $this->logger->error('Failed to process message queue event', [
                'event_class' => get_class($event),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Push message queue consumption notification to client.
     */
    private function pushMessageQueueNotification(
        TopicEntity $topicEntity,
        MessageQueueEntity $message
    ): void {
        try {
            $pushData = $this->buildMessageQueuePushData($topicEntity, $message);
            $this->pushNotification($topicEntity->getUserId(), $pushData);

            $this->logger->info('Message queue notification pushed successfully', [
                'topic_id' => $topicEntity->getId(),
                'message_id' => $message->getId(),
                'user_id' => $topicEntity->getUserId(),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to push message queue notification', [
                'topic_id' => $topicEntity->getId(),
                'message_id' => $message->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Build push data structure for message queue consumption.
     */
    private function buildMessageQueuePushData(
        TopicEntity $topicEntity,
        MessageQueueEntity $message
    ): array {
        return [
            'type' => 'seq',
            'seq' => [
                'delightful_id' => '',
                'seq_id' => '',
                'message_id' => '',
                'refer_message_id' => '',
                'sender_message_id' => '',
                'conversation_id' => $topicEntity->getChatConversationId(),
                'organization_code' => $topicEntity->getUserOrganizationCode(),
                'message' => [
                    'type' => 'be_delightful_message_queue_change',
                    'project_id' => (string) $topicEntity->getProjectId(),
                    'topic_id' => (string) $topicEntity->getId(),
                    'chat_topic_id' => $topicEntity->getChatTopicId(),
                    'message_id' => (string) $message->getId(),
                ],
            ],
        ];
    }

    /**
     * Push notification via WebSocket.
     */
    private function pushNotification(string $userId, array $pushData): void
    {
        $delightfulId = $this->getDelightfulIdByUserId($userId);

        if (empty($delightfulId)) {
            $this->logger->warning('Cannot get delightfulId for user', ['user_id' => $userId]);
            return;
        }

        $this->logger->info('Pushing message queue notification', [
            'delightful_id' => $delightfulId,
            'topic_id' => $pushData['seq']['message']['topic_id'],
            'message_id' => $pushData['seq']['message']['message_id'],
        ]);

        // Push via WebSocket
        SocketIOUtil::sendIntermediate(
            SocketEventType::Intermediate,
            $delightfulId,
            $pushData
        );
    }

    /**
     * Get delightfulId by userId.
     */
    private function getDelightfulIdByUserId(string $userId): string
    {
        try {
            $userEntity = $this->userDomainService->getUserById($userId);
            return $userEntity?->getDelightfulId() ?? '';
        } catch (Throwable $e) {
            $this->logger->error('Failed to get delightfulId by userId', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return '';
        }
    }
}
