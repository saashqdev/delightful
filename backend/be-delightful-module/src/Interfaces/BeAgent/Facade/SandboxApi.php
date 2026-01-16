<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\user Type;
use App\ErrorCode\AgentErrorCode;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use App\Interfaces\Authorization\Web\Magicuser Authorization;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\DTO\user MessageDTO;
use Delightful\BeDelightful\Application\SuperAgent\Service\AgentAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\HandleTaskMessageAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\ProjectAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\TopicAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\TopicTaskAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\WorkspaceAppService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\user DomainService;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateProjectRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\InitSandboxRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\SaveTopicRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\SaveWorkspaceRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\UpgradeSandboxRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\InitSandboxResponseDTO;
use Hyperf\HttpServer\Contract\RequestInterface;

class SandboxApi extends AbstractApi 
{
 
    public function __construct( 
    protected RequestInterface $request, 
    protected WorkspaceAppService $workspaceAppService, 
    protected TopicTaskAppService $topicTaskAppService, 
    protected HandleTaskMessageAppService $taskAppService, 
    protected ProjectAppService $projectAppService, 
    protected TopicAppService $topicAppService, 
    protected user DomainService $userDomainService, 
    protected HandleTaskMessageAppService $handleTaskMessageAppService, 
    protected AgentAppService $agentAppService, ) 
{
 parent::__construct($request); 
}
 #[ApiResponse('low_code')] 
    public function getSandboxStatus(RequestContext $requestContext): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $topicId = $this->request->input('topic_id', ''); if (empty($topicId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic_id is required'); 
}
 // var_dump($this->getAuthorization(), requestContext===== ); $topic = $this->topicAppService->getTopicById((int) $topicId); $sandboxId = $topic->getSandboxId(); if (empty($sandboxId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'sandbox_id is required'); 
}
 $result = $this->agentAppService->getSandboxStatus($sandboxId); if (! $result->isSuccess()) 
{
 ExceptionBuilder::throw(AgentErrorCode::SANDBOX_NOT_FOUND, $result->getMessage()); 
}
 return $result->toArray(); 
}
 // CreateTaskSupportagenttoolcustomthree types SchemaUsingapi-keyRow #[ApiResponse('low_code')] 
    public function initSandboxByApiKey(RequestContext $requestContext, InitSandboxRequestDTO $requestDTO): array 
{
 // FromRequestin CreateDTOand validate Parameter $requestDTO = InitSandboxRequestDTO::fromRequest($this->request); // FromRequestin CreateDTO $apiKey = $this->getApiKey(); if (empty($apiKey)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'The api key of header is required'); 
}
 // $userinfo RequestDTO = new user info RequestDTO(['uid' => $apiKey]); // Determinerequest headers whether Existmagic-user-id $magicuser Id = $this->request->header('magic-user-id', ''); $userEntity = $this->handleTaskMessageAppService->getuser Authorization($apiKey, $magicuser Id); if (empty($userEntity)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'user_not_found'); 
}
 $magicuser Authorization = Magicuser Authorization::fromuser Entity($userEntity); $requestContext->setuser Authorization($magicuser Authorization); return $this->initSandbox($requestContext, $requestDTO, $magicuser Authorization); 
}
 
    public function initSandboxByAuthorization(RequestContext $requestContext): array 
{
 $topicId = $this->request->input('topic_id', ''); $requestContext->setuser Authorization($this->getAuthorization()); if (empty($topicId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic_id is required'); 
}
 $topic = $this->topicAppService->getTopic($requestContext, (int) $topicId); $projectId = $topic->getProjectId(); $project = $this->projectAppService->getProjectNotuser Id((int) $projectId); $workspaceId = (string) $project->getWorkspaceId(); $requestDTO = new InitSandboxRequestDTO(); $requestDTO->setWorkspaceId($workspaceId); $requestDTO->setProjectId($projectId); $requestDTO->setTopicId($topicId); $requestDTO->setTopicMode($topic->getTopicMode()); return $this->initSandbox($requestContext, $requestDTO, $this->getAuthorization()); 
}
 
    public function initSandbox(RequestContext $requestContext, InitSandboxRequestDTO $requestDTO, $magicuser Authorization): array 
{
 // Determineworkspace whether Existdoes not existInitializeworkspace $this->initWorkspace($requestContext, $requestDTO); // DetermineItemwhether Existdoes not existInitializeItem $this->initProject($requestContext, $requestDTO, $magicuser Authorization->getId()); // Determinetopic whether Existdoes not existInitializetopic $this->initTopic($requestContext, $requestDTO); // $requestDTO->setConversationId($requestDTO->getTopicId()); $initSandboxResponseDTO = new InitSandboxResponseDTO(); $initSandboxResponseDTO->setWorkspaceId($requestDTO->getWorkspaceId()); $initSandboxResponseDTO->setProjectId($requestDTO->getProjectId()); $initSandboxResponseDTO->setProjectMode($requestDTO->getProjectMode()); $initSandboxResponseDTO->setTopicId($requestDTO->getTopicId()); $initSandboxResponseDTO->setChatTopicId($requestDTO->getChatTopicId()); // $initSandboxResponseDTO->setConversationId($requestDTO->getTopicId()); $dataIsolation = new DataIsolation(); $dataIsolation->setcurrent user Id((string) $magicuser Authorization->getId()); $dataIsolation->setThirdPartyOrganizationCode($magicuser Authorization->getOrganizationCode()); $dataIsolation->setcurrent OrganizationCode($magicuser Authorization->getOrganizationCode()); $dataIsolation->setuser Type(user Type::Human); // $dataIsolation = new DataIsolation($userEntity->getId(), $userEntity->getOrganizationCode(), $userEntity->getWorkDir()); $userMessage = [ 'chat_topic_id' => $requestDTO->getChatTopicId(), 'topic_id' => (int) $requestDTO->getTopicId(), // 'chat_conversation_id' => $requestDTO->getConversationId(), 'prompt' => $requestDTO->getPrompt(), 'attachments' => null, 'mentions' => null, 'agent_user_id' => (string) $magicuser Authorization->getId(), 'project_mode' => $requestDTO->getProjectMode(), 'topic_mode' => $requestDTO->getTopicMode(), 'task_mode' => '', 'model_id' => $requestDTO->getModelId(), ]; $userMessageDTO = user MessageDTO::fromArray($userMessage); // $this->handleApiMessageAppService->handleApiMessage($dataIsolation, $userMessageDTO); // $userMessageDTO->setAgentMode($requestDTO->getProjectMode()); $result = $this->handleTaskMessageAppService->initSandbox($dataIsolation, $userMessageDTO); $initSandboxResponseDTO->setSandboxId($result['sandbox_id']); $initSandboxResponseDTO->setTaskId($result['task_id']); return $initSandboxResponseDTO->toArray(); 
}
 
    public function initWorkspace(RequestContext $requestContext, InitSandboxRequestDTO &$requestDTO) 
{
 // Determineworkspace whether Existdoes not existInitializeworkspace $workspaceId = $requestDTO->getWorkspaceId(); if ($workspaceId > 0) 
{
 $workspace = $this->workspaceAppService->getWorkspaceDetail($requestContext, (int) $workspaceId); if (empty($workspace->getId())) 
{
 // Exceptionworkspace does not exist ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'workspace_not_found'); 
}
 
}
 else 
{
 $saveWorkspaceRequestDTO = new SaveWorkspaceRequestDTO(); $saveWorkspaceRequestDTO->setWorkspaceName('Default workspace'); $workspace = $this->workspaceAppService->createWorkspace($requestContext, $saveWorkspaceRequestDTO); $workspaceId = $workspace->getId(); 
}
 $requestDTO->setWorkspaceId($workspaceId); 
}
 
    public function initProject(RequestContext $requestContext, InitSandboxRequestDTO &$requestDTO, string $userId): void 
{
 // DetermineItemwhether Existdoes not existInitializeItem $projectId = $requestDTO->getProjectId(); if ($projectId > 0) 
{
 $project = $this->projectAppService->getProject($requestContext, (int) $projectId); if (empty($project->getId())) 
{
 // ExceptionItemdoes not exist ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'project_not_found'); 
}
 
}
 else 
{
 $saveProjectRequestDTO = new CreateProjectRequestDTO(); $saveProjectRequestDTO->setProjectName('Default project'); $saveProjectRequestDTO->setWorkspaceId((string) $requestDTO->getWorkspaceId()); $saveProjectRequestDTO->setProjectMode($requestDTO->getProjectMode()); $project = $this->projectAppService->createProject($requestContext, $saveProjectRequestDTO); if (! empty($project['project'])) 
{
 $projectId = $project['project']['id']; 
}
 else 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'project_not_found'); 
}
 
}
 $requestDTO->setProjectId($projectId); 
}
 
    public function initTopic(RequestContext $requestContext, InitSandboxRequestDTO &$requestDTO): void 
{
 // Determinetopic whether Existdoes not existInitializetopic $topicId = $requestDTO->getTopicId(); if ($topicId > 0) 
{
 $topic = $this->topicAppService->getTopic($requestContext, (int) $topicId); if (empty($topic->getId())) 
{
 // Exceptiontopic does not exist ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic_not_found'); 
}
 $chatTopicId = $topic->getChatTopicId(); 
}
 else 
{
 $saveTopicRequestDTO = new SaveTopicRequestDTO(); $saveTopicRequestDTO->setTopicName('Default topic'); $saveTopicRequestDTO->setProjectId((string) $requestDTO->getProjectId()); $saveTopicRequestDTO->setWorkspaceId((string) $requestDTO->getWorkspaceId()); $saveTopicRequestDTO->setProjectMode($requestDTO->getProjectMode()); $saveTopicRequestDTO->setTopicMode($requestDTO->getTopicMode()); $topic = $this->topicAppService->createTopicNotValidateAccessibleProject($requestContext, $saveTopicRequestDTO); if (! empty($topic->getId())) 
{
 $topicId = $topic->getId(); $chatTopicId = $topic->getChatTopicId(); 
}
 else 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic_not_found'); 
}
 
}
 $requestDTO->setChatTopicId($chatTopicId); $requestDTO->setTopicId($topicId); 
}
 /** * sandbox . */ #[ApiResponse('low_code')] 
    public function upgradeSandbox(RequestContext $requestContext): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); // Validate RequestParameter $requestDTO = UpgradeSandboxRequestDTO::fromRequest($this->request); $messageId = $requestDTO->getMessageId(); $contextType = $requestDTO->getContextType(); if (empty($messageId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'message_id is required'); 
}
 // CreateDataObject $dataIsolation = new DataIsolation(); $dataIsolation->setcurrent user Id((string) $this->getAuthorization()->getId()); $dataIsolation->setcurrent OrganizationCode($this->getAuthorization()->getOrganizationCode()); $dataIsolation->setThirdPartyOrganizationCode($this->getAuthorization()->getOrganizationCode()); $dataIsolation->setuser Type(user Type::Human); // call ApplyServiceexecute $result = $this->agentAppService->upgradeSandbox($dataIsolation, $messageId, $contextType); if (! $result->isSuccess()) 
{
 ExceptionBuilder::throw(AgentErrorCode::SANDBOX_UPGRADE_FAILED, $result->getMessage()); 
}
 return $result->toArray(); 
}
 
}
 
