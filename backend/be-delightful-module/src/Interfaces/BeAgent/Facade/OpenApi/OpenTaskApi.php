<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade\OpenApi;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestCoContext;
use App\Infrastructure\Util\Context\RequestContext;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\DTO\user MessageDTO;
use Delightful\BeDelightful\Application\SuperAgent\Service\AgentAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\HandleTaskMessageAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\ProjectAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\TaskAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\TopicAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\TopicTaskAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\WorkspaceAppService;
use Delightful\BeDelightful\Domain\SuperAgent\Entity\ValueObject\TaskStatus;
use Delightful\BeDelightful\Domain\SuperAgent\Service\user DomainService;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\SandboxStatus;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateAgentTaskRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateScriptTaskRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetTaskFilesRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\Facade\AbstractApi;
use Exception;
use Hyperf\HttpServer\Contract\RequestInterface;

class OpenTaskApi extends AbstractApi 
{
 
    public function __construct( 
    protected RequestInterface $request, 
    protected WorkspaceAppService $workspaceAppService, 
    protected TopicTaskAppService $topicTaskAppService, 
    protected HandleTaskMessageAppService $handleTaskAppService, 
    protected TaskAppService $taskAppService, 
    protected ProjectAppService $projectAppService, 
    protected TopicAppService $topicAppService, 
    protected user DomainService $userDomainService, 
    protected HandleTaskMessageAppService $handleTaskMessageAppService, 
    protected AgentAppService $agentAppService, ) 
{
 parent::__construct($request); 
}
 /** * Summary of updateTaskStatus. */ #[ApiResponse('low_code')] 
    public function updateTaskStatus(RequestContext $requestContext): array 
{
 $taskId = $this->request->input('task_id', ''); $status = $this->request->input('status', ''); $id = $this->request->input('id', ''); // Iftask_idEmptyUsingid if (empty($taskId)) 
{
 $taskId = $id; 
}
 if (empty($taskId) || empty($status)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'task_id_or_status_is_required'); 
}
 $taskEntity = $this->taskAppService->getTaskById((int) $taskId); if (empty($taskEntity)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'task_not_found'); 
}
 // check user whether Havepermission UpdateTaskStatus $userAuthorization = RequestCoContext::getuser Authorization(); if ($taskEntity->getuser Id() !== $userAuthorization->getId()) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'user_not_authorized'); 
}
 $dataIsolation = new DataIsolation(); // Set user Authorizeinfo $dataIsolation->setcurrent user Id((string) $userAuthorization->getId()); $status = TaskStatus::from($status); $this->topicTaskAppService->updateTaskStatus($dataIsolation, $taskEntity, $status); return []; 
}
 
    public function handApiKey(RequestContext $requestContext, &$userEntity) 
{
 // FromRequestin CreateDTO $apiKey = $this->getApiKey(); if (empty($apiKey)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'The api key of header is required'); 
}
 $userEntity = $this->handleTaskMessageAppService->getuser Authorization($apiKey, ''); if (empty($userEntity)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'user_not_found'); 
}
 $magicuser Authorization = Magicuser Authorization::fromuser Entity($userEntity); $requestContext->setuser Authorization($magicuser Authorization); 
}
 /** * Summary of agentTask. */ #[ApiResponse('low_code')] 
    public function agentTask(RequestContext $requestContext, CreateAgentTaskRequestDTO $requestDTO): array 
{
 // FromRequestin CreateDTOand validate Parameter $requestDTO = CreateAgentTaskRequestDTO::fromRequest($this->request); $magicuser Authorization = RequestCoContext::getuser Authorization(); // Determinetopic whether Existdoes not existInitializetopic $topicId = $requestDTO->getTopicId(); $topicDTO = $this->topicAppService->getTopic($requestContext, (int) $topicId); $requestDTO->setConversationId((string) $topicId); $dataIsolation = new DataIsolation(); $dataIsolation->setcurrent user Id((string) $magicuser Authorization->getId()); $dataIsolation->setThirdPartyOrganizationCode($magicuser Authorization->getThirdPlatformOrganizationCode()); $dataIsolation->setcurrent OrganizationCode($magicuser Authorization->getOrganizationCode()); $dataIsolation->setuser Type($magicuser Authorization->getuser Type()); $sandboxId = $topicDTO->getSandboxId(); try 
{
 // check including erwhether Normal $result = $this->agentAppService->getSandboxStatus($sandboxId); if ($result->getStatus() !== SandboxStatus::RUNNING) 
{
 // including erNormalRowneed Rowincluding er $userMessage = [ 'chat_topic_id' => $topicDTO->getChatTopicId(), 'topic_id' => (int) $topicDTO->getChatTopicId(), 'chat_conversation_id' => $requestDTO->getConversationId(), 'prompt' => $requestDTO->getPrompt(), 'attachments' => null, 'mentions' => null, 'agent_user_id' => (string) $magicuser Authorization->getId(), 'agent_mode' => '', 'task_mode' => '', ]; $userMessageDTO = user MessageDTO::fromArray($userMessage); $result = $this->handleTaskMessageAppService->initSandbox($dataIsolation, $userMessageDTO); if (empty($result['sandbox_id'])) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'the sandbox cannot running,please check the sandbox status'); 
}
 $sandboxId = $result['sandbox_id']; $taskEntity = $this->handleTaskAppService->getTask((int) $result['task_id']); 
}
 else 
{
 $taskEntity = $this->handleTaskAppService->getTaskBySandboxId($sandboxId); 
}
 $userMessage = [ 'chat_topic_id' => (string) $topicDTO->getChatTopicId(), 'chat_conversation_id' => (string) $topicDTO->getChatConversationId(), 'prompt' => $requestDTO->getPrompt(), 'attachments' => null, 'mentions' => null, 'agent_user_id' => (string) $magicuser Authorization->getId(), 'agent_mode' => '', 'task_mode' => $taskEntity->getTaskMode(), ]; $userMessageDTO = user MessageDTO::fromArray($userMessage); $this->handleTaskMessageAppService->sendChatMessage($dataIsolation, $userMessageDTO); 
}
 catch (Exception $e) 
{
 // $this->agentAppService->sendInterruptMessage($dataIsolation, $topicDTO->getSandboxId(), (string) $taskEntity->getId(), 'Task has been terminated .'); ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, $e->getMessage()); 
}
 return $taskEntity->toArray(); 
}
 /** * Summary of scriptTask. */ #[ApiResponse('low_code')] 
    public function scriptTask(RequestContext $requestContext, CreateScriptTaskRequestDTO $requestDTO): array 
{
 // FromRequestin CreateDTOand validate Parameter $requestDTO = CreateScriptTaskRequestDTO::fromRequest($this->request); $taskEntity = $this->handleTaskAppService->getTask((int) $requestDTO->getTaskId()); // Determinetopic whether Existdoes not existInitializetopic $topicId = $taskEntity->getTopicId(); $topicDTO = $this->topicAppService->getTopic($requestContext, (int) $topicId); // check including erwhether Normal $result = $this->agentAppService->getSandboxStatus($topicDTO->getSandboxId()); if ($result->getStatus() !== SandboxStatus::RUNNING) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'sandbox_not_running'); 
}
 $requestDTO->setSandboxId($topicDTO->getSandboxId()); try 
{
 $this->handleTaskMessageAppService->executeScriptTask($requestDTO); 
}
 catch (Exception $e) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'execute_script_task_failed'); 
}
 return []; 
}
 /** * Summary of getOpenApiTaskAttachments. */ #[ApiResponse('low_code')] 
    public function getOpenApiTaskAttachments(RequestContext $requestContext): array 
{
 // GetTaskFileRequestDTO // $requestDTO = GetTaskFilesRequestDTO::fromRequest($this->request); $id = $this->request->input('id', ''); if (empty($id)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'id is required'); 
}
 $userAuthorization = RequestCoContext::getuser Authorization(); if (empty($userAuthorization)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'user_authorization_not_found'); 
}
 return $this->workspaceAppService->getTaskAttachments($userAuthorization, (int) $id, 1, 100); 
}
 // GetTaskinfo 
    public function getTask(RequestContext $requestContext): array 
{
 $taskId = $this->request->input('task_id', ''); if (empty($taskId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'task_id is required'); 
}
 $task = $this->taskAppService->getTaskById((int) $taskId); if (empty($task)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'task_not_found'); 
}
 $userAuthorization = RequestCoContext::getuser Authorization(); if ($task->getuser Id() !== $userAuthorization->getId()) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'user_not_authorized'); 
}
 return $task->toArray(); 
}
 
}
 
