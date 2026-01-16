<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Util\Context\RequestContext;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\Service\MessageQueueAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\MessageQueueprocess AppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\MessageScheduleAppService;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\ConsumeMessageQueueRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateMessageQueueRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateMessageScheduleRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\query MessageQueueRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\query MessageScheduleLogsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\query MessageScheduleRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpdateMessageQueueRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpdateMessageScheduleRequestDTO;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Throwable;
#[ApiResponse('low_code')]

class MessageApi extends AbstractApi 
{
 
    public function __construct( 
    protected RequestInterface $request, 
    protected MessageQueueAppService $messageQueueAppService, 
    protected MessageQueueprocess AppService $messageQueueprocess AppService, 
    protected MessageScheduleAppService $messageScheduleAppService, 
    protected TranslatorInterface $translator, ) 
{
 parent::__construct($request); 
}
 /** * Create message queue. * * @param RequestContext $requestContext Request context * @return array Operation result containing queue_id and status * @throws BusinessException If parameters are invalid or operation fails * @throws Throwable */ 
    public function createMessageQueue(RequestContext $requestContext): array 
{
 // Set user authorization information $requestContext->setuser Authorization($this->getAuthorization()); // Create DTO from request $requestDTO = CreateMessageQueueRequestDTO::fromRequest($this->request); // Call application service to handle business logic return $this->messageQueueAppService->createMessage($requestContext, $requestDTO); 
}
 /** * Update message queue. * * @param RequestContext $requestContext Request context * @param string $id Message queue ID * @return array Operation result containing queue_id and status * @throws BusinessException If parameters are invalid or operation fails * @throws Throwable */ 
    public function updateMessageQueue(RequestContext $requestContext, string $id): array 
{
 // Set user authorization information $requestContext->setuser Authorization($this->getAuthorization()); // Create DTO from request $requestDTO = UpdateMessageQueueRequestDTO::fromRequest($this->request); // Call application service to handle business logic return $this->messageQueueAppService->updateMessage($requestContext, (int) $id, $requestDTO); 
}
 /** * delete message queue. * * @param RequestContext $requestContext Request context * @param string $id Message queue ID * @return array Operation result containing affected rows * @throws BusinessException If parameters are invalid or operation fails * @throws Throwable */ 
    public function deleteMessageQueue(RequestContext $requestContext, string $id): array 
{
 // Set user authorization information $requestContext->setuser Authorization($this->getAuthorization()); // Call application service to handle business logic return $this->messageQueueAppService->deleteMessage($requestContext, (int) $id); 
}
 /** * query message queues. * * @param RequestContext $requestContext Request context * @return array query result containing list and total * @throws BusinessException If parameters are invalid or operation fails * @throws Throwable */ 
    public function queryMessageQueues(RequestContext $requestContext): array 
{
 // Set user authorization information $requestContext->setuser Authorization($this->getAuthorization()); // Create DTO from request $requestDTO = query MessageQueueRequestDTO::fromRequest($this->request); // Call application service to handle business logic return $this->messageQueueAppService->queryMessages($requestContext, $requestDTO); 
}
 /** * Consume message queue. * * @param RequestContext $requestContext Request context * @param string $id Message queue ID * @return array Operation result containing status * @throws BusinessException If parameters are invalid or operation fails * @throws Throwable */ 
    public function consumeMessageQueue(RequestContext $requestContext, string $id): array 
{
 // 1. Set user authorization information $requestContext->setuser Authorization($this->getAuthorization()); // 2. Create DTO from request (for future extensions) $requestDTO = ConsumeMessageQueueRequestDTO::fromRequest($this->request); // 3. Get and validate message (permission check) $messageId = (int) $id; $userId = $requestContext->getuser Authorization()->getId(); $messageEntity = $this->messageQueueAppService->getMessageQueueEntity($messageId, $userId); if (! $messageEntity) 
{
 throw new BusinessException( $this->translator->trans('message_queue.message_not_found') ); 
}
 // 4. check if message can be consumed if (! $messageEntity->canBeConsumed()) 
{
 throw new BusinessException( $this->translator->trans('message_queue.cannot_consume_message') ); 
}
 // 5. Call MessageQueueprocess AppService to send message $sendResult = $this->messageQueueprocess AppService->processSingleMessage($messageId); // 6. Return result return [ 'status' => $sendResult['success'] ? 'completed' : 'failed', 'success' => $sendResult['success'], 'error_message' => $sendResult['error_message'] ?? null, ]; 
}
 /** * Create message schedule. * * @param RequestContext $requestContext Request context * @return array Operation result containing schedule_id * @throws BusinessException If parameters are invalid or operation fails * @throws Throwable */ 
    public function createMessageSchedule(RequestContext $requestContext): array 
{
 // Set user authorization information $requestContext->setuser Authorization($this->getAuthorization()); // Create DTO from request $requestDTO = CreateMessageScheduleRequestDTO::fromRequest($this->request); // Call application service to handle business logic return $this->messageScheduleAppService->createSchedule($requestContext, $requestDTO); 
}
 /** * Update message schedule. * * @param RequestContext $requestContext Request context * @param string $id Message schedule ID * @return array Operation result containing schedule_id * @throws BusinessException If parameters are invalid or operation fails * @throws Throwable */ 
    public function updateMessageSchedule(RequestContext $requestContext, string $id): array 
{
 // Set user authorization information $requestContext->setuser Authorization($this->getAuthorization()); // Create DTO from request $requestDTO = UpdateMessageScheduleRequestDTO::fromRequest($this->request); // Call application service to handle business logic return $this->messageScheduleAppService->updateSchedule($requestContext, (int) $id, $requestDTO); 
}
 /** * delete message schedule. * * @param RequestContext $requestContext Request context * @param string $id Message schedule ID * @return array Operation result containing affected rows * @throws BusinessException If parameters are invalid or operation fails * @throws Throwable */ 
    public function deleteMessageSchedule(RequestContext $requestContext, string $id): array 
{
 // Set user authorization information $requestContext->setuser Authorization($this->getAuthorization()); // Call application service to handle business logic return $this->messageScheduleAppService->deleteSchedule($requestContext, (int) $id); 
}
 /** * query message schedules. * * @param RequestContext $requestContext Request context * @return array query result containing list and total * @throws BusinessException If parameters are invalid or operation fails * @throws Throwable */ 
    public function queryMessageSchedules(RequestContext $requestContext): array 
{
 // Set user authorization information $requestContext->setuser Authorization($this->getAuthorization()); // Create DTO from request $requestDTO = query MessageScheduleRequestDTO::fromRequest($this->request); // Call application service to handle business logic return $this->messageScheduleAppService->querySchedules($requestContext, $requestDTO); 
}
 /** * Get message schedule detail. * * @param RequestContext $requestContext Request context * @param string $id Message schedule ID * @return array Message schedule detail * @throws BusinessException If parameters are invalid or operation fails * @throws Throwable */ 
    public function getMessageScheduleDetail(RequestContext $requestContext, string $id): array 
{
 // Set user authorization information $requestContext->setuser Authorization($this->getAuthorization()); // Call application service to handle business logic return $this->messageScheduleAppService->getScheduleDetail($requestContext, (int) $id); 
}
 /** * Get message schedule execution logs. * * @param RequestContext $requestContext Request context * @param string $id Message schedule ID * @return array Execution logs result with total count and list containing executed_at, task_name, workspace info, project info, topic info, status, and error_message * @throws BusinessException If parameters are invalid or operation fails * @throws Throwable */ 
    public function getMessageScheduleLogs(RequestContext $requestContext, string $id): array 
{
 // Set user authorization information $requestContext->setuser Authorization($this->getAuthorization()); // Create DTO from request $requestDTO = query MessageScheduleLogsRequestDTO::fromRequest($this->request); // Call application service to handle business logic return $this->messageScheduleAppService->getScheduleLogs($requestContext, (int) $id, $requestDTO); 
}
 /** * execute message schedule for testing purpose. * execute MessageScheduledTaskTest. */ 
    public function executeMessageScheduleForTest(RequestContext $requestContext, string $id): array 
{
 // Convert string ID to integer $messageScheduleId = (int) $id; // Call the messageScheduleCallback method directly for testing return MessageScheduleAppService::messageScheduleCallback($messageScheduleId); 
}
 
}
 
