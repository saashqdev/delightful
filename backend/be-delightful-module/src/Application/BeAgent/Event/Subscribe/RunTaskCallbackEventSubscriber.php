<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Event\Subscribe;

use App\Domain\Chat\Entity\ValueObject\SocketEventType;
use App\Domain\Contact\Service\Magicuser DomainService;
use App\Infrastructure\Util\SocketIO\SocketIOUtil;
use Dtyq\AsyncEvent\Kernel\Annotation\Asynclist ener;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\ProjectMode;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Event\RunTaskCallbackEvent;
use Delightful\BeDelightful\Domain\SuperAgent\Repository\Facade\TaskMessageRepositoryInterface;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TopicDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\WorkspaceDomainService;
use Hyperf\Codec\Json;
use Hyperf\Event\Annotation\list ener;
use Hyperf\Event\Contract\list enerInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Throwable;
/** * RunTaskCallbackEventEventlist ener - recording summary complete Detect. */ #[Asynclist ener] #[list ener]

class RunTaskCallbackEventSubscriber implements list enerInterface 
{
 
    private LoggerInterface $logger; 
    public function __construct( LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(static::class); 
}
 /** * list en to events. * * @return array Array of event classes to listen to */ 
    public function listen(): array 
{
 return [ RunTaskCallbackEvent::class, ]; 
}
 /** * process the event. * * @param object $event Event object */ 
    public function process(object $event): void 
{
 // Type check if (! $event instanceof RunTaskCallbackEvent) 
{
 return; 
}
 // check recording summary completion $this->checkrecord ingSummaryCompletion($event); 
}
 /** * check recording summary completion and send notification. * Detectrecording summary whether complete Ifcomplete PushNotice. */ 
    private function checkrecord ingSummaryCompletion(RunTaskCallbackEvent $event): void 
{
 try 
{
 // 1. check TaskStatus $status = $event->getTaskMessage()->getPayload()->getStatus(); $taskStatus = TaskStatus::tryFrom($status); if ($taskStatus === null) 
{
 $this->logger->warning('checkrecord ingSummary Task status not found for recording summary check', [ 'task_id' => $event->getTaskId(), 'topic_id' => $event->getTopicId(), 'status' => $status, ]); return; 
}
 // check TaskStatuswhether as ERROR or FINISHED if ($taskStatus !== TaskStatus::ERROR && $taskStatus !== TaskStatus::FINISHED) 
{
 return; 
}
 // 2. query Taskuser Messagecheck whether Have summary_task mark // Using topicId + taskId + sender_type query IndexReturn user Message $taskMessageRepository = di(TaskMessageRepositoryInterface::class); $userMessages = $taskMessageRepository->finduser MessagesByTopicIdAndTaskId($event->getTopicId(), (string) $event->getTaskId()); $hasSummaryTask = false; foreach ($userMessages as $message) 
{
 $rawContent = $message->getRawContent(); if (! empty($rawContent)) 
{
 // raw_content directly yes dynamic_params JSON $dynamicParams = Json::decode($rawContent); if (isset($dynamicParams['summary_task']) && $dynamicParams['summary_task'] === true) 
{
 $hasSummaryTask = true; $this->logger->info('checkrecord ingSummary Found summary_task marker', [ 'task_id' => $event->getTaskId(), 'topic_id' => $event->getTopicId(), ]); break; 
}
 
}
 
}
 // 3. IfDon't have summary_task mark PushNotice if (! $hasSummaryTask) 
{
 $this->logger->info('checkrecord ingSummary No summary_task marker found, skipping notification', [ 'task_id' => $event->getTaskId(), 'topic_id' => $event->getTopicId(), ]); return; 
}
 // 4. Gettopic info check Schema $topicDomainService = di(TopicDomainService::class); $topicEntity = $topicDomainService->getTopicById($event->getTopicId()); if ($topicEntity === null) 
{
 $this->logger->warning('checkrecord ingSummary Topic not found for recording summary check', [ 'topic_id' => $event->getTopicId(), 'task_id' => $event->getTaskId(), ]); return; 
}
 // check topic Schemawhether as summary if ($topicEntity->getTopicMode() !== ProjectMode::SUMMARY->value) 
{
 return; 
}
 // 5. Getuser info PushNotice $userId = $event->getuser Id(); $magicuser DomainService = di(Magicuser DomainService::class); $userEntity = $magicuser DomainService->getuser ById($userId); if ($userEntity === null) 
{
 $this->logger->warning('checkrecord ingSummary user not found for recording summary notification', [ 'user_id' => $userId, 'task_id' => $event->getTaskId(), 'topic_id' => $event->getTopicId(), ]); return; 
}
 // ThroughServicequery Itemworkspace NameDependency domain/app  $projectName = ''; $workspaceName = ''; try 
{
 $projectDomainService = di(ProjectDomainService::class); $projectEntity = $projectDomainService->getProjectNotuser Id($topicEntity->getProjectId()); $projectName = $projectEntity?->getProjectName() ?? ''; $workspaceDomainService = di(WorkspaceDomainService::class); $workspace = $workspaceDomainService->getWorkspaceDetail($topicEntity->getWorkspaceId()); $workspaceName = $workspace?->getName() ?? ''; 
}
 catch (Throwable $e) 
{
 $this->logger->warning('checkrecord ingSummary fetch project/workspace name failed', [ 'topic_id' => $event->getTopicId(), 'project_id' => $topicEntity->getProjectId(), 'workspace_id' => $topicEntity->getWorkspaceId(), 'error' => $e->getMessage(), ]); 
}
 // PushData $pushData = [ 'type' => 'recording_summary_result', 'recording_summary_result' => [ 'workspace_id' => (string) $topicEntity->getWorkspaceId(), 'workspace_name' => $workspaceName, 'project_id' => (string) $topicEntity->getProjectId(), 'project_name' => $projectName, 'topic_id' => (string) $topicEntity->getId(), 'organization_code' => $event->getOrganizationCode(), 'success' => $taskStatus === TaskStatus::FINISHED, 'timestamp' => time(), ], ]; // PushMessagegive Client SocketIOUtil::sendIntermediate( SocketEventType::Intermediate, $userEntity->getMagicId(), $pushData ); $this->logger->info('checkrecord ingSummary recording summary complete notice has been pushed', [ 'user_id' => $userId, 'magic_id' => $userEntity->getMagicId(), 'topic_id' => $topicEntity->getId(), 'task_id' => $event->getTaskId(), 'status' => $taskStatus->value, 'success' => $taskStatus === TaskStatus::FINISHED, ]); 
}
 catch (Throwable $e) 
{
 $this->logger->error('checkrecord ingSummary Failed to send recording summary completion notification', [ 'task_id' => $event->getTaskId(), 'topic_id' => $event->getTopicId(), 'user_id' => $event->getuser Id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), ]); 
}
 
}
 
}
 
