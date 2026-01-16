<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Interfaces\SuperAgent\Facade;

use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use Dtyq\ApiResponse\Annotation\ApiResponse;
use Delightful\BeDelightful\Application\SuperAgent\Service\AgentFileAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\FileBatchAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\FileManagementAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\Fileprocess AppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\FileVersionAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\SandboxFileNotificationAppService;
use Delightful\BeDelightful\Application\SuperAgent\Service\WorkspaceAppService;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\WorkFileUtil;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\BatchCopyFileRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\Batchdelete FilesRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\BatchMoveFileRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\BatchSaveFileContentRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\BatchSaveProjectFilesRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\check BatchOperationStatusRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CopyFileRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateBatchDownloadRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateFileRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\delete DirectoryRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetFileUrlsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetFileVersionsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\MoveFileRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\ProjectUploadTokenRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\RefreshStsTokenRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\ReplaceFileRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\RollbackFileToVersionRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\SandboxFileNotificationRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\SaveProjectFileRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\TopicUploadTokenRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\WorkspaceAttachmentsRequestDTO;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\RateLimit\Annotation\RateLimit;
use Qbhy\HyperfAuth\AuthManager;
#[ApiResponse('low_code')]

class FileApi extends AbstractApi 
{
 
    public function __construct( 
    private readonly Fileprocess AppService $fileprocess AppService, 
    private readonly FileBatchAppService $fileBatchAppService, 
    private readonly FileManagementAppService $fileManagementAppService, 
    private readonly FileVersionAppService $fileVersionAppService, 
    protected WorkspaceAppService $workspaceAppService, 
    protected RequestInterface $request, 
    protected AgentFileAppService $agentFileAppService, 
    private readonly SandboxFileNotificationAppService $sandboxFileNotificationAppService, ) 
{
 parent::__construct($request); 
}
 /** * Batchprocess According tofileKeycheck whether ExistExistSkipdoes not existSave. * only task_idattachmentsParameter,ParameterFromTaskin automatic Get. * * @param RequestContext $requestContext RequestContext * @return array process Result */ 
    public function processAttachments(RequestContext $requestContext): array 
{
 // GetRequestParameter $attachments = $this->request->input('attachments', []); $sandboxId = (string) $this->request->input('sandbox_id', ''); $organizationCode = $this->request->input('organization_code', ''); // ParameterValidate if (empty($attachments)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'file.attachments_required'); 
}
 if (empty($sandboxId)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'file.sandbox_id_required'); 
}
 if (empty($organizationCode)) 
{
 // IfDon't haveorganization code ,UsingDefault value $organizationCode = 'default'; 
}
 // call ApplyServiceprocess ,nullServiceautomatic Gettopic_id return $this->fileprocess AppService->processAttachmentsArray( $attachments, $sandboxId, $organizationCode, null // topic_id,ServiceAccording totaskIdautomatic Get ); 
}
 /** * Refresh STS Token. * * @param RequestContext $requestContext RequestContext * @return array RefreshResult */ 
    public function refreshStsToken(RequestContext $requestContext): array 
{
 $token = $this->request->header('token', ''); if (empty($token)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_required'); 
}
 if ($token !== config('super-magic.sandbox.token', '')) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_invalid'); 
}
 // CreateDTOFromRequestin Parse Data $requestData = $this->request->all(); $refreshStsTokenDTO = RefreshStsTokenRequestDTO::fromRequest($requestData); return $this->fileprocess AppService->refreshStsToken($refreshStsTokenDTO); 
}
 /** * Refresh STS Token. * * @param RequestContext $requestContext RequestContext * @return array RefreshResult */ 
    public function refreshTmpStsToken(RequestContext $requestContext): array 
{
 $token = $this->request->header('token', ''); if (empty($token)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_required'); 
}
 if ($token !== config('super-magic.sandbox.token', '')) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'token_invalid'); 
}
 // CreateDTOFromRequestin Parse Data $requestData = $this->request->all(); $refreshStsTokenDTO = RefreshStsTokenRequestDTO::fromRequest($requestData); return $this->fileprocess AppService->refreshStsToken($refreshStsTokenDTO); 
}
 
    public function workspaceAttachments(RequestContext $requestContext): array 
{
 // $topicId = $this->request->input('topic_id', ''); // $commitHash = $this->request->input('commit_hash', ''); // $sandboxId = $this->request->input('sandbox_id', ''); // $folder = $this->request->input('folder', ''); // $dir = $this->request->input('dir', ''); $requestDTO = new WorkspaceAttachmentsRequestDTO(); $requestDTO = $requestDTO->fromRequest($this->request); if (empty($requestDTO->getTopicId())) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'topic_id_required'); 
}
 if (empty($requestDTO->getCommitHash())) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'commit_hash_required'); 
}
 if (empty($requestDTO->getSandboxId())) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'sandbox_id_required'); 
}
 if (empty($requestDTO->getDir())) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'dir_required'); 
}
 if (empty($requestDTO->getFolder())) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'folder_required'); 
}
 return $this->fileprocess AppService->workspaceAttachments($requestDTO); 
}
 /** * BatchSaveFileContent. * Concurrencyexecute sandbox FileEditOSSSave. * * @param RequestContext $requestContext RequestContext * @return array BatchSaveResult */ 
    public function saveFileContent(RequestContext $requestContext): array 
{
 $requestData = $this->request->all(); if (empty($requestData)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterValidate Failed, 'files_array_required'); 
}
 $requestContext->setuser Authorization($this->getAuthorization()); $userAuthorization = $requestContext->getuser Authorization(); $batchSaveDTO = BatchSaveFileContentRequestDTO::fromRequest($requestData); return $this->fileprocess AppService->batchSaveFileContent($batchSaveDTO, $userAuthorization); 
}
 
    public function deleteFile(RequestContext $requestContext, string $id): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); return $this->fileManagementAppService->deleteFile($requestContext, (int) $id); 
}
 
    public function deleteDirectory(RequestContext $requestContext): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); // GetRequestDataCreateDTO $requestDTO = delete DirectoryRequestDTO::fromRequest($this->request); // call ApplyService return $this->fileManagementAppService->deleteDirectory($requestContext, $requestDTO); 
}
 
    public function batchdelete Files(RequestContext $requestContext): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); // GetRequestDataCreateDTO $requestDTO = Batchdelete FilesRequestDTO::fromRequest($this->request); // call ApplyService return $this->fileManagementAppService->batchdelete Files($requestContext, $requestDTO); 
}
 
    public function renameFile(RequestContext $requestContext, string $id): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $targetName = $this->request->input('target_name', ''); // Validate target_name parameter using WorkFileUtil if (! WorkFileUtil::isValidFileName($targetName)) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::FILE_ILLEGAL_NAME, 'file.illegal_file_name'); 
}
 return $this->fileManagementAppService->renameFile($requestContext, (int) $id, $targetName); 
}
 
    public function moveFile(RequestContext $requestContext, string $id): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); // Get request data and create DTO $requestDTO = MoveFileRequestDTO::fromRequest($this->request); return $this->fileManagementAppService->moveFile( $requestContext, (int) $id, (int) $requestDTO->getTargetParentId(), (int) $requestDTO->getPreFileId(), ! empty($requestDTO->getTargetProjectId()) ? (int) $requestDTO->getTargetProjectId() : null, $requestDTO->getKeepBothFileIds() ); 
}
 
    public function batchMoveFile(RequestContext $requestContext): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); // Get request data and create DTO $requestDTO = BatchMoveFileRequestDTO::fromRequest($this->request); // Call application service return $this->fileManagementAppService->batchMoveFile($requestContext, $requestDTO); 
}
 
    public function batchCopyFile(RequestContext $requestContext): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); // Get request data and create DTO $requestDTO = BatchCopyFileRequestDTO::fromRequest($this->request); // Call application service return $this->fileManagementAppService->batchCopyFile($requestContext, $requestDTO); 
}
 
    public function copyFile(RequestContext $requestContext, string $id): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); // Get request data and create DTO $requestDTO = CopyFileRequestDTO::fromRequest($this->request); return $this->fileManagementAppService->copyFile( $requestContext, (int) $id, (int) $requestDTO->getTargetParentId(), (int) $requestDTO->getPreFileId(), ! empty($requestDTO->getTargetProjectId()) ? (int) $requestDTO->getTargetProjectId() : null, $requestDTO->getKeepBothFileIds() ); 
}
 /** * Create batch download task. * * @param RequestContext $requestContext Request context * @return array Create result */ #[RateLimit(create: 3, capacity: 3, key: 'batch_download_create')] 
    public function createBatchDownload(RequestContext $requestContext): array 
{
 // Set user authorization info $requestContext->setuser Authorization($this->getAuthorization()); // Get request data and create DTO $requestData = $this->request->all(); $requestDTO = CreateBatchDownloadRequestDTO::fromRequest($requestData); // Call application service $responseDTO = $this->fileBatchAppService->createBatchDownload($requestContext, $requestDTO); return $responseDTO->toArray(); 
}
 /** * check batch download status. * * @param RequestContext $requestContext Request context * @return array query result */ #[RateLimit(create: 30, capacity: 30, key: 'batch_download_check')] 
    public function checkBatchDownload(RequestContext $requestContext): array 
{
 // Set user authorization info $requestContext->setuser Authorization($this->getAuthorization()); // Get batch key from request $batchKey = (string) $this->request->input('batch_key', ''); if (empty($batchKey)) 
{
 ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, 'batch_key_required'); 
}
 // Call application service $responseDTO = $this->fileBatchAppService->checkBatchDownload($requestContext, $batchKey); return $responseDTO->toArray(); 
}
 /** * check batch operation status. * * @param RequestContext $requestContext Request context * @return array query result */ #[RateLimit(create: 30, capacity: 30, key: 'batch_operation_check')] 
    public function checkBatchOperationStatus(RequestContext $requestContext): array 
{
 // Set user authorization info $requestContext->setuser Authorization($this->getAuthorization()); // Get request data and create DTO $requestDTO = check BatchOperationStatusRequestDTO::fromRequest($this->request); // Call application service $responseDTO = $this->fileManagementAppService->checkBatchOperationStatus($requestContext, $requestDTO); return $responseDTO->toArray(); 
}
 /** * GetItemFile uploadSTS Token. * * @param RequestContext $requestContext RequestContext * @return array GetResult */ 
    public function getProjectUploadToken(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // GetRequestDataCreateDTO $requestData = $this->request->all(); $requestDTO = ProjectUploadTokenRequestDTO::fromRequest($requestData); // call ApplyService return $this->fileManagementAppService->getProjectUploadToken($requestContext, $requestDTO); 
}
 /** * Gettopic File uploadSTS Token. * * @param RequestContext $requestContext RequestContext * @return array GetResult */ 
    public function getTopicUploadToken(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // GetRequestDataCreateDTO $requestData = $this->request->all(); $requestDTO = TopicUploadTokenRequestDTO::fromRequest($requestData); // call ApplyService return $this->fileManagementAppService->getTopicUploadToken($requestContext, $requestDTO); 
}
 /** * CreateFileor Folder. * * @param RequestContext $requestContext RequestContext * @return array CreateResult */ 
    public function createFile(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // GetRequestDataCreateDTO $requestDTO = CreateFileRequestDTO::fromRequest($this->request); // call ApplyService return $this->fileManagementAppService->createFile($requestContext, $requestDTO); 
}
 /** * SaveItemFile. * * @param RequestContext $requestContext RequestContext * @return array SaveResult */ 
    public function saveProjectFile(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // GetRequestDataCreateDTO $requestData = $this->request->all(); $requestDTO = SaveProjectFileRequestDTO::fromRequest($requestData); // call ApplyService return $this->fileManagementAppService->saveFile($requestContext, $requestDTO); 
}
 /** * BatchSaveItemFile. * * @param RequestContext $requestContext RequestContext * @return array BatchSaveResultReturn FileIDArray */ 
    public function batchSaveProjectFiles(RequestContext $requestContext): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // GetRequestDataCreateDTO $requestDTO = BatchSaveProjectFilesRequestDTO::fromRequest($this->request); // call ApplyService return $this->fileManagementAppService->batchSaveFiles($requestContext, $requestDTO); 
}
 /** * Handle sandbox file notification. * This endpoint doesn't require user authentication, uses token-based auth instead. * * @param RequestContext $requestContext Request context * @return array Response data */ 
    public function handleSandboxNotification(RequestContext $requestContext): array 
{
 // Create DTO from request $requestDTO = SandboxFileNotificationRequestDTO::fromRequest($this->request); // Call application service without user context return $this->sandboxFileNotificationAppService->handleNotificationWithoutAuth($requestDTO); 
}
 /** * Get file name by file ID. * * @param int $id File ID * @return array File name response */ 
    public function getFileByName(int $id): array 
{
 // Call app service to get file name return $this->fileprocess AppService->getFileNameById($id); 
}
 /** * Get file basic information by file ID. * * @param int $id File ID * @return array File basic information response (file name, current version, organization code) */ 
    public function getFileinfo (int $id): array 
{
 // Call app service to get file basic information return $this->fileprocess AppService->getFileinfo ById($id); 
}
 /** * GetFileVersionlist . * * @param RequestContext $requestContext RequestContext * @param string $id FileID * @return array FileVersionlist */ 
    public function getFileVersions(RequestContext $requestContext, string $id): array 
{
 // Set user Authorizeinfo $requestContext->setuser Authorization($this->getAuthorization()); // GetRequestDataCreateDTO $requestDTO = GetFileVersionsRequestDTO::fromRequest($this->request); $requestDTO->setFileId((int) $id); // Set file ID from route parameter // call ApplyService $responseDTO = $this->fileVersionAppService->getFileVersions($requestContext, $requestDTO); return $responseDTO->toArray(); 
}
 /** * FileRollbackspecified Version. * * @param RequestContext $requestContext RequestContext * @param string $id FileID * @return array RollbackResult */ 
    public function rollbackFileToVersion(RequestContext $requestContext, string $id): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $requestDTO = RollbackFileToVersionRequestDTO::fromRequest($this->request); $requestDTO->setFileId((int) $id); $responseDTO = $this->fileVersionAppService->rollbackFileToVersion($requestContext, $requestDTO); return $responseDTO->toArray(); 
}
 /** * GetFileURLlist . * * @param RequestContext $requestContext RequestContext * @return array FileURLlist * @throws BusinessException IfParameterInvalidThrowException */ 
    public function getFileUrls(RequestContext $requestContext): array 
{
 // GetRequestDTO $dto = GetFileUrlsRequestDTO::fromRequest($this->request); if (! empty($dto->getToken())) 
{
 // Token return $this->fileManagementAppService->getFileUrlsByAccessToken( $dto->getFileIds(), $dto->getToken(), $dto->getDownloadMode(), $dto->getFileVersions() ); 
}
 // Set user Authorizeinfo $requestContext->setuser Authorization(di(AuthManager::class)->guard(name: 'web')->user()); // BuildoptionsParameter $options = []; $options['cache'] = false; // call ApplyService return $this->fileManagementAppService->getFileUrls( $requestContext, $dto->getProjectId(), $dto->getFileIds(), $dto->getDownloadMode(), $options, $dto->getFileVersions() // New addition: pass directly as method parameter ); 
}
 /** * Replace file with new file. * * @param RequestContext $requestContext Request context * @return array Replaced file information */ 
    public function replaceFile(RequestContext $requestContext): array 
{
 $requestContext->setuser Authorization($this->getAuthorization()); $fileId = (int) $this->request->route('id'); $requestDTO = ReplaceFileRequestDTO::fromRequest($this->request); return $this->fileManagementAppService->replaceFile($requestContext, $fileId, $requestDTO); 
}
 
}
 
