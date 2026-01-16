<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\Contact\Entity\ValueObject\user Type;
use App\ErrorCode\AgentErrorCode;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\CoContext;
use App\Infrastructure\Util\Context\RequestContext;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\Service\AgentAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\TopicAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\WorkspaceAppService;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\check pointRollbackcheck RequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\check pointRollbackRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\check pointRollbackStartRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\delete TopicRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\DuplicateTopiccheck RequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\DuplicateTopicRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetTopicAttachmentsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetTopicMessagesByTopicIdRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\SaveTopicRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\check pointRollbackcheck ResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\check pointRollbackResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\DuplicateTopicStatusResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\TopicMessagesResponseDTO;
use Exception;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Qbhy\HyperfAuth\AuthManager;
use Throwable;
#[ApiResponse('low_code')]

class TopicApi extends AbstractApi 
{
 
    public function __construct( 
    protected RequestInterface $request, 
    protected WorkspaceAppService $workspaceAppService, 
    protected TopicAppService $topicAppService, 
    protected TranslatorInterface $translator, 
    protected AgentAppService $agentAppService, ) 
{
 parent::__construct($request); 
}
 /** * Gettopic info . * @param mixed $id */ 
    public function getTopic(RequestContext $requestContext, $id): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); return $this->topicAppService->getTopic($requestContext, (int) $id)->toArray(); 
}
 /** * Savetopic Createor Update * Interfaceprocess HTTPRequestResponseNot contain. * * @param RequestContext $requestContext RequestContext * @return array Resultincluding topic ID * @throws BusinessException IfParameterInvalidor FailedThrowException * @throws Throwable */ 
    public function createTopic(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // FromRequestCreateDTO $requestDTO = SaveTopicRequestDTO::fromRequest($this->request); // call ApplyServiceprocess return $this->topicAppService->createTopic($requestContext, $requestDTO)->toArray(); 
}
 /** * Savetopic Createor Update * Interfaceprocess HTTPRequestResponseNot contain. * * @param RequestContext $requestContext RequestContext * @return array Resultincluding topic ID * @throws BusinessException IfParameterInvalidor FailedThrowException * @throws Throwable */ 
    public function updateTopic(RequestContext $requestContext, string $id): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // FromRequestCreateDTO $requestDTO = SaveTopicRequestDTO::fromRequest($this->request); $requestDTO->id = $id; // call ApplyServiceprocess return $this->topicAppService->updateTopic($requestContext, $requestDTO)->toArray(); 
}
 /** * delete topic delete  * Interfaceprocess HTTPRequestResponseNot contain. * * @param RequestContext $requestContext RequestContext * @return array Resultincluding delete topic ID * @throws BusinessException IfParameterInvalidor FailedThrowException * @throws Exception */ 
    public function deleteTopic(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // FromRequestCreateDTO $requestDTO = delete TopicRequestDTO::fromRequest($this->request); // call ApplyServiceprocess return $this->topicAppService->deleteTopic($requestContext, $requestDTO)->toArray(); 
}
 /** * Renametopic . */ 
    public function renameTopic(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // topic id $authorization = $requestContext->getuser Authorization(); $topicId = $this->request->input('id', 0); $userQuestion = $this->request->input('user_question', ''); $language = $this->translator->getLocale(); return $this->topicAppService->renameTopic($authorization, (int) $topicId, $userQuestion, $language); 
}
 /** * Gettopic list . */ 
    public function getTopicAttachments(RequestContext $requestContext): array 
{
 // Using fromRequest MethodFromRequestin Create DTOCanFromroute Parameterin Get topic_id $dto = GetTopicAttachmentsRequestDTO::fromRequest($this->request); if (! empty($dto->getToken())) 
{
 // Token return $this->topicAppService->getTopicAttachmentsByAccessToken($dto); 
}
 // Loginuser Using $requestContext->setuser Authorization(di(AuthManager::class)->guard(name: 'web')->user()); $userAuthorization = $requestContext->getuser Authorization(); return $this->topicAppService->getTopicAttachments($userAuthorization, $dto); 
}
 /** * Throughtopic IDGetMessagelist .x. * * @param RequestContext $requestContext RequestContext * @return array Messagelist Paginginfo */ 
    public function getMessagesByTopicId(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // FromRequestCreateDTO $dto = GetTopicMessagesByTopicIdRequestDTO::fromRequest($this->request); // topic Messagewhether yes $topicItemDTO = $this->topicAppService->getTopic($requestContext, $dto->getTopicId()); if ($topicItemDTO->getuser Id() !== $requestContext->getuser Authorization()->getId()) 
{
 return ['list' => [], 'total' => 0]; 
}
 // call ApplyService $result = $this->workspaceAppService->getMessagesByTopicId( $dto->getTopicId(), $dto->getPage(), $dto->getPageSize(), $dto->getSortDirection() ); // BuildResponse $response = new TopicMessagesResponseDTO($result['list'], $result['total']); return $response->toArray(); 
}
 /** * Rollbacksandbox specified checkpoint. * * @param RequestContext $requestContext RequestContext * @param string $id topic ID * @return array RollbackResult */ #[ApiResponse('low_code')] 
    public function rollbackcheck point(RequestContext $requestContext, string $id): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = check pointRollbackRequestDTO::fromRequest($this->request); $topicId = $id; $targetMessageId = $requestDTO->getTargetMessageId(); if (empty($topicId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic_id is required'); 
}
 if (empty($targetMessageId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'target_message_id is required'); 
}
 $authorization = $this->getAuthorization(); $dataIsolation = new DataIsolation(); $dataIsolation->setcurrent user Id((string) $authorization->getId()); $dataIsolation->setThirdPartyOrganizationCode($authorization->getOrganizationCode()); $dataIsolation->setcurrent OrganizationCode($authorization->getOrganizationCode()); $dataIsolation->setuser Type(user Type::Human); $dataIsolation->setLanguage(CoContext::getLanguage()); $sandboxId = $this->agentAppService->ensureSandboxInitialized($dataIsolation, (int) $topicId); $result = $this->agentAppService->rollbackcheck point($sandboxId, $targetMessageId); if (! $result->isSuccess()) 
{
 ExceptionBuilder::throw(AgentErrorCode::SANDBOX_NOT_FOUND, $result->getMessage()); 
}
 $responseDTO = new check pointRollbackResponseDTO(); $responseDTO->setTargetMessageId($targetMessageId); $responseDTO->setMessage($result->getMessage()); return $responseDTO->toArray(); 
}
 /** * StartRollbacksandbox specified checkpointmark Statusdelete . * * @param RequestContext $requestContext RequestContext * @param string $id topic ID * @return array RollbackResult */ #[ApiResponse('low_code')] 
    public function rollbackcheck pointStart(RequestContext $requestContext, string $id): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = check pointRollbackStartRequestDTO::fromRequest($this->request); $topicId = $id; $targetMessageId = $requestDTO->getTargetMessageId(); if (empty($topicId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic_id is required'); 
}
 if (empty($targetMessageId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'target_message_id is required'); 
}
 $authorization = $this->getAuthorization(); $dataIsolation = new DataIsolation(); $dataIsolation->setcurrent user Id((string) $authorization->getId()); $dataIsolation->setThirdPartyOrganizationCode($authorization->getOrganizationCode()); $dataIsolation->setcurrent OrganizationCode($authorization->getOrganizationCode()); $dataIsolation->setuser Type(user Type::Human); $dataIsolation->setLanguage(CoContext::getLanguage()); // call ApplyServicerollbackcheck pointStartMethod $this->agentAppService->rollbackcheck pointStart($dataIsolation, (int) $topicId, $targetMessageId); return []; 
}
 /** * SubmitRollbacksandbox specified checkpointdelete recalled status Message. * * @param RequestContext $requestContext RequestContext * @param string $id topic ID * @return array SubmitResult */ #[ApiResponse('low_code')] 
    public function rollbackcheck pointCommit(RequestContext $requestContext, string $id): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $topicId = $id; if (empty($topicId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic_id is required'); 
}
 $authorization = $this->getAuthorization(); // CreateDataObject $dataIsolation = new DataIsolation(); $dataIsolation->setcurrent user Id((string) $authorization->getId()); $dataIsolation->setThirdPartyOrganizationCode($authorization->getOrganizationCode()); $dataIsolation->setcurrent OrganizationCode($authorization->getOrganizationCode()); $dataIsolation->setuser Type(user Type::Human); $dataIsolation->setLanguage(CoContext::getLanguage()); // call ApplyServicerollbackcheck pointCommitMethod $this->agentAppService->rollbackcheck pointCommit($dataIsolation, (int) $topicId); return []; 
}
 /** * UndoRollbacksandbox checkpointrecalled status MessageResumeas NormalStatus. * * @param RequestContext $requestContext RequestContext * @param string $id topic ID * @return array UndoResult */ #[ApiResponse('low_code')] 
    public function rollbackcheck pointUndo(RequestContext $requestContext, string $id): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $topicId = $id; if (empty($topicId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic_id is required'); 
}
 $authorization = $this->getAuthorization(); // CreateDataObject $dataIsolation = new DataIsolation(); $dataIsolation->setcurrent user Id((string) $authorization->getId()); $dataIsolation->setThirdPartyOrganizationCode($authorization->getOrganizationCode()); $dataIsolation->setcurrent OrganizationCode($authorization->getOrganizationCode()); $dataIsolation->setuser Type(user Type::Human); $dataIsolation->setLanguage(CoContext::getLanguage()); // call ApplyServicerollbackcheck pointUndoMethod $this->agentAppService->rollbackcheck pointUndo($dataIsolation, (int) $topicId); return []; 
}
 /** * check Rollbacksandbox specified checkpointRow. * * @param RequestContext $requestContext RequestContext * @param string $id topic ID * @return array check Result */ #[ApiResponse('low_code')] 
    public function rollbackcheck pointcheck (RequestContext $requestContext, string $id): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = check pointRollbackcheck RequestDTO::fromRequest($this->request); $topicId = $id; $targetMessageId = $requestDTO->getTargetMessageId(); if (empty($topicId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic_id is required'); 
}
 if (empty($targetMessageId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'target_message_id is required'); 
}
 $authorization = $this->getAuthorization(); $dataIsolation = new DataIsolation(); $dataIsolation->setcurrent user Id((string) $authorization->getId()); $dataIsolation->setThirdPartyOrganizationCode($authorization->getOrganizationCode()); $dataIsolation->setcurrent OrganizationCode($authorization->getOrganizationCode()); $dataIsolation->setuser Type(user Type::Human); $dataIsolation->setLanguage(CoContext::getLanguage()); $result = $this->agentAppService->rollbackcheck pointcheck ($dataIsolation, (int) $topicId, $targetMessageId); if (! $result->isSuccess()) 
{
 ExceptionBuilder::throw(AgentErrorCode::SANDBOX_NOT_FOUND, $result->getMessage()); 
}
 $responseDTO = new check pointRollbackcheck ResponseDTO(); $responseDTO->setCanRollback((bool) $result->getDataValue('can_rollback', false)); return $responseDTO->toArray(); 
}
 /** * Duplicate topic (synchronous) - blocks until completion. * * @param RequestContext $requestContext Request context * @param string $id Source topic ID * @return array complete result with topic info * @throws BusinessException If validation fails or operation fails */ #[ApiResponse('low_code')] 
    public function duplicateChat(RequestContext $requestContext, string $id): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); // Get request DTO $dto = DuplicateTopicRequestDTO::fromRequest($this->request); // Call synchronous method return $this->topicAppService->duplicateTopic($requestContext, $id, $dto); 
}
 /** * Duplicate topic (asynchronous) - returns immediately with task_id. * * @param RequestContext $requestContext Request context * @param string $id Source topic ID * @return array Task info with task_id * @throws BusinessException If validation fails or operation fails */ #[ApiResponse('low_code')] 
    public function duplicateChatAsync(RequestContext $requestContext, string $id): array 
{
 // Set user authorization $requestContext->setuser Authorization($this->getAuthorization()); // Get request DTO $dto = DuplicateTopicRequestDTO::fromRequest($this->request); // Call asynchronous method return $this->topicAppService->duplicateChatAsync($requestContext, $id, $dto); 
}
 /** * check topic duplication status. * * @param RequestContext $requestContext Request context * @param string $id Source topic ID * @return array Duplication status info * @throws BusinessException If validation fails or operation fails */ #[ApiResponse('low_code')] 
    public function duplicateChatcheck (RequestContext $requestContext, string $id): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); $userAuthorization = $requestContext->getuser Authorization(); // GetRequestDTO $dto = DuplicateTopiccheck RequestDTO::fromRequest($this->request); try 
{
 // call ApplyService $result = $this->topicAppService->checkDuplicateChatStatus($requestContext, $dto->getTaskKey()); $responseDTO = DuplicateTopicStatusResponseDTO::fromArray($result); return $responseDTO->toArray(); 
}
 catch (Throwable $e) 
{
 // TODO: AddErrorLogrecord throw $e; 
}
 
}
 
}
 
