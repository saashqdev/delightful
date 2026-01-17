<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Event\Subscribe;

use App\Domain\Chat\Entity\ValueObject\SocketEventType;
use App\Domain\Contact\Service\DelightfulUserDomainService;
use App\Infrastructure\Util\SocketIO\SocketIOUtil;
use Delightful\AsyncEvent\Kernel\Annotation\AsyncListener;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\ProjectMode;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\BeAgent\Event\RunTaskCallbackEvent;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskMessageRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\WorkspaceDomainService;
use Hyperf\Codec\Json;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * RunTaskCallbackEvent listener - recording summary completion detection.
 */
#[AsyncListener]
#[Listener]
class RunTaskCallbackEventSubscriber implements ListenerInterface
{
    private LoggerInterface $logger;

    public function __construct(
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(static::class);
    }

    /**
     * Listen to events.
     *
     * @return array Array of event classes to listen to
     */
    public function listen(): array
    {
        return [
            RunTaskCallbackEvent::class,
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
        if (! $event instanceof RunTaskCallbackEvent) {
            return;
        }

        // Check recording summary completion
        $this->checkRecordingSummaryCompletion($event);
    }

    /**
     * Check recording summary completion and send notification.
     * Detects if recording summary is complete, and if so, sends a notification.
     */
    private function checkRecordingSummaryCompletion(RunTaskCallbackEvent $event): void
    {
        try {
            // 1. Check task status
            $status = $event->getTaskMessage()->getPayload()->getStatus();
            $taskStatus = TaskStatus::tryFrom($status);
            if ($taskStatus === null) {
                $this->logger->warning('checkRecordingSummary Task status not found for recording summary check', [
                    'task_id' => $event->getTaskId(),
                    'topic_id' => $event->getTopicId(),
                    'status' => $status,
                ]);
                return;
            }
            // Check if task status is ERROR or FINISHED
            if ($taskStatus !== TaskStatus::ERROR && $taskStatus !== TaskStatus::FINISHED) {
                return;
            }

            // 2. Query user messages for this task and check for summary_task marker
            // Use topicId + taskId + sender_type for query, leveraging index and returning only user messages
            $taskMessageRepository = di(TaskMessageRepositoryInterface::class);
            $userMessages = $taskMessageRepository->findUserMessagesByTopicIdAndTaskId($event->getTopicId(), (string) $event->getTaskId());

            $hasSummaryTask = false;
            foreach ($userMessages as $message) {
                $rawContent = $message->getRawContent();
                if (! empty($rawContent)) {
                    // raw_content directly stores the JSON of dynamic_params
                    $dynamicParams = Json::decode($rawContent);
                    if (isset($dynamicParams['summary_task'])
                        && $dynamicParams['summary_task'] === true) {
                        $hasSummaryTask = true;
                        $this->logger->info('checkRecordingSummary Found summary_task marker', [
                            'task_id' => $event->getTaskId(),
                            'topic_id' => $event->getTopicId(),
                        ]);
                        break;
                    }
                }
            }

            // 3. If no summary_task marker found, don't send notification
            if (! $hasSummaryTask) {
                $this->logger->info('checkRecordingSummary No summary_task marker found, skipping notification', [
                    'task_id' => $event->getTaskId(),
                    'topic_id' => $event->getTopicId(),
                ]);
                return;
            }

            // 4. Get topic information and check mode (double guarantee)
            $topicDomainService = di(TopicDomainService::class);
            $topicEntity = $topicDomainService->getTopicById($event->getTopicId());
            if ($topicEntity === null) {
                $this->logger->warning('checkRecordingSummary Topic not found for recording summary check', [
                    'topic_id' => $event->getTopicId(),
                    'task_id' => $event->getTaskId(),
                ]);
                return;
            }

            // Check if topic mode is summary
            if ($topicEntity->getTopicMode() !== ProjectMode::SUMMARY->value) {
                return;
            }

            // 5. Get user information and send notification
            $userId = $event->getUserId();
            $delightfulUserDomainService = di(DelightfulUserDomainService::class);
            $userEntity = $delightfulUserDomainService->getUserById($userId);

            if ($userEntity === null) {
                $this->logger->warning('checkRecordingSummary User not found for recording summary notification', [
                    'user_id' => $userId,
                    'task_id' => $event->getTaskId(),
                    'topic_id' => $event->getTopicId(),
                ]);
                return;
            }

            // Query project and workspace names through domain service (can only depend on domain/app layer)
            $projectName = '';
            $workspaceName = '';
            try {
                $projectDomainService = di(ProjectDomainService::class);
                $projectEntity = $projectDomainService->getProjectNotUserId($topicEntity->getProjectId());
                $projectName = $projectEntity?->getProjectName() ?? '';

                $workspaceDomainService = di(WorkspaceDomainService::class);
                $workspace = $workspaceDomainService->getWorkspaceDetail($topicEntity->getWorkspaceId());
                $workspaceName = $workspace?->getName() ?? '';
            } catch (Throwable $e) {
                $this->logger->warning('checkRecordingSummary fetch project/workspace name failed', [
                    'topic_id' => $event->getTopicId(),
                    'project_id' => $topicEntity->getProjectId(),
                    'workspace_id' => $topicEntity->getWorkspaceId(),
                    'error' => $e->getMessage(),
                ]);
            }

            // Prepare push data
            $pushData = [
                'type' => 'recording_summary_result',
                'recording_summary_result' => [
                    'workspace_id' => (string) $topicEntity->getWorkspaceId(),
                    'workspace_name' => $workspaceName,
                    'project_id' => (string) $topicEntity->getProjectId(),
                    'project_name' => $projectName,
                    'topic_id' => (string) $topicEntity->getId(),
                    'organization_code' => $event->getOrganizationCode(),
                    'success' => $taskStatus === TaskStatus::FINISHED,
                    'timestamp' => time(),
                ],
            ];

            // Send message to client
            SocketIOUtil::sendIntermediate(
                SocketEventType::Intermediate,
                $userEntity->getDelightfulId(),
                $pushData
            );

            $this->logger->info('checkRecordingSummary Recording summary completion notification sent', [
                'user_id' => $userId,
                'delightful_id' => $userEntity->getDelightfulId(),
                'topic_id' => $topicEntity->getId(),
                'task_id' => $event->getTaskId(),
                'status' => $taskStatus->value,
                'success' => $taskStatus === TaskStatus::FINISHED,
            ]);
        } catch (Throwable $e) {
            $this->logger->error('checkRecordingSummary Failed to send recording summary completion notification', [
                'task_id' => $event->getTaskId(),
                'topic_id' => $event->getTopicId(),
                'user_id' => $event->getUserId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
