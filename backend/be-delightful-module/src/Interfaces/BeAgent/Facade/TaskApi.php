<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use App\Infrastructure\Util\ShadowCode\ShadowCode;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\Service\AgentAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\FileConverterAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\HandleTaskMessageAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\ProjectAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\TaskAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\TopicAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\TopicTaskAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\WorkspaceAppService;
use Delightful\BeDelightful\Domain\SuperAgent\Constant\ConvertStatusEnum;
use Delightful\BeDelightful\Domain\SuperAgent\Service\user DomainService;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\ConvertFilesRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetTaskFilesRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\TopicTaskMessageDTO;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Qbhy\HyperfAuth\AuthManager;
use Throwable;
#[ApiResponse('low_code')]

class TaskApi extends AbstractApi 
{
 
    protected LoggerInterface $logger; 
    public function __construct( 
    protected RequestInterface $request, 
    protected WorkspaceAppService $workspaceAppService, 
    protected TopicTaskAppService $topicTaskAppService, 
    protected HandleTaskMessageAppService $handleTaskAppService, 
    protected TaskAppService $taskAppService, 
    protected FileConverterAppService $fileConverterAppService, LoggerFactory $loggerFactory, 
    protected ProjectAppService $projectAppService, 
    protected TopicAppService $topicAppService, 
    protected user DomainService $userDomainService, 
    protected HandleTaskMessageAppService $handleTaskMessageAppService, 
    protected AgentAppService $agentAppService, ) 
{
 $this->logger = $loggerFactory->get(get_class($this)); parent::__construct($request); 
}
 /** * delivery topic TaskMessage. * * @param RequestContext $requestContext RequestContext * @return array Result * @throws BusinessException IfParameterInvalidor FailedThrowException */ 
    public function deliverMessage(RequestContext $requestContext): array 
{
 // From header in Get token Field $token = $this->request->header('token', ''); if (empty($token)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_required'); 
}
 // From env Getsandbox token ThenPairsandbox token Request token whether Consistent $sandboxToken = config('super-magic.sandbox.token', ''); if ($sandboxToken !== $token) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_invalid'); 
}
 // Viewwhether $isConfusion = $this->request->input('obfuscated', false); if ($isConfusion) 
{
 // process $rawData = ShadowCode::unShadow($this->request->input('data', '')); 
}
 else 
{
 $rawData = $this->request->input('data', ''); 
}
 $requestData = json_decode($rawData, true); // FromRequestin CreateDTO $messageDTO = TopicTaskMessageDTO::fromArray($requestData); // call ApplyServiceRowMessagedelivery if (config('super-magic.message.process_mode') === 'direct') 
{
 return $this->topicTaskAppService->handleTopicTaskMessage($messageDTO); 
}
 return $this->topicTaskAppService->deliverTopicTaskMessage($messageDTO); 
}
 
    public function resumeTask(RequestContext $requestContext): array 
{
 // From header in Get token Field $token = $this->request->header('token', ''); if (empty($token)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_required'); 
}
 // From env Getsandbox token ThenPairsandbox token Request token whether Consistent $sandboxToken = config('super-magic.sandbox.token', ''); if ($sandboxToken !== $token) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_invalid'); 
}
 $sandboxId = $this->request->input('sandbox_id', ''); $isInit = $this->request->input('is_init', false); $this->taskAppService->sendContinueMessageToSandbox($sandboxId, $isInit); return []; 
}
 /** * GetTaskunder All. * * @param RequestContext $requestContext RequestContext * @return array list Paginginfo * @throws BusinessException IfParameterInvalidThrowException */ 
    public function getTaskAttachments(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); $userAuthorization = $requestContext->getuser Authorization(); // GetTaskFileRequestDTO $dto = GetTaskFilesRequestDTO::fromRequest($this->request); // call ApplyService return $this->workspaceAppService->getTaskAttachments( $userAuthorization, $dto->getId(), $dto->getPage(), $dto->getPageSize() ); 
}
 /** * BatchConvertFile. * * @param RequestContext $requestContext RequestContext * @return array ConvertResult */ 
    public function convertFiles(RequestContext $requestContext): array 
{
 // GetRequestDTO $dto = ConvertFilesRequestDTO::fromRequest($this->request); // Set user Authorizeinfo $requestContext->setuser Authorization(di(AuthManager::class)->guard(name: 'web')->user()); $userAuthorization = $requestContext->getuser Authorization(); try 
{
 // call ApplyService return $this->fileConverterAppService->convertFiles($userAuthorization, $dto); 
}
 catch (Throwable $e) 
{
 $this->logger->error('Convert files API failed', [ 'user_id' => $userAuthorization->getId(), 'organization_code' => $userAuthorization->getOrganizationCode(), 'project_id' => $dto->project_id, 'file_ids_count' => count($dto->file_ids), 'convert_type' => $dto->convert_type, 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString(), ]); throw $e; 
}
 
}
 /** * check FileConvertStatus. * * @param RequestContext $requestContext RequestContext * @return array Statuscheck Result */ 
    public function checkFileConvertStatus(RequestContext $requestContext): array 
{
 $taskKey = $this->request->input('task_key'); if (empty($taskKey)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::VALIDATE_FAILED, 'validation.required'); 
}
 // Set user Authorizeinfo $requestContext->setuser Authorization(di(AuthManager::class)->guard(name: 'web')->user()); $userAuthorization = $requestContext->getuser Authorization(); try 
{
 $result = $this->fileConverterAppService->checkFileConvertStatus($userAuthorization, $taskKey); // IfStatusyes FAILEDThrowException if ($result->getStatus() === ConvertStatusEnum::FAILED->value) 
{
 $this->logger->error('File conversion failed', [ 'task_key' => $taskKey, 'user_id' => $userAuthorization->getId(), 'organization_code' => $userAuthorization->getOrganizationCode(), 'status' => $result->getStatus(), 'total_files' => $result->getTotalFiles(), 'success_count' => $result->getSuccessCount(), 'batch_id' => $result->getBatchId(), 'convert_type' => $result->getConvertType(), ]); ExceptionBuilder::throw(SuperAgentErrorCode::FILE_CONVERT_FAILED, 'file.convert_failed'); 
}
 // IfStatusyes COMPLETED yes Don't haveDownloadAddressNoteTaskoccurred ed Error if ($result->getStatus() === ConvertStatusEnum::COMPLETED->value && empty($result->getDownloadUrl())) 
{
 $this->logger->error('File conversion completed but no download URL available', [ 'task_key' => $taskKey, 'user_id' => $userAuthorization->getId(), 'organization_code' => $userAuthorization->getOrganizationCode(), 'status' => $result->getStatus(), 'total_files' => $result->getTotalFiles(), 'success_count' => $result->getSuccessCount(), 'batch_id' => $result->getBatchId(), 'convert_type' => $result->getConvertType(), ]); ExceptionBuilder::throw(SuperAgentErrorCode::FILE_CONVERT_FAILED, 'file.convert_failed'); 
}
 return $result->toArray(); 
}
 catch (Throwable $e) 
{
 $this->logger->error('check file convert status failed', [ 'task_key' => $taskKey, 'user_id' => $userAuthorization->getId(), 'organization_code' => $userAuthorization->getOrganizationCode(), 'error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTraceAsString(), ]); throw $e; 
}
 
}
 
}
 
