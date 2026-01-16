<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Application\SuperAgent\Service;

use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use Delightful\BeDelightful\Domain\SuperAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\SuperAgent\Service\TaskFileVersionDomainService;
use Delightful\BeDelightful\ErrorCode\SuperAgentErrorCode;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\CreateFileVersionRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\GetFileVersionsRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Request\RollbackFileToVersionRequestDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\CreateFileVersionResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\GetFileVersionsResponseDTO;
use Delightful\BeDelightful\Interfaces\SuperAgent\DTO\Response\RollbackFileToVersionResponseDTO;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class FileVersionAppService extends AbstractAppService 
{
 
    private readonly LoggerInterface $logger; 
    public function __construct( 
    private readonly ProjectDomainService $projectDomainService, 
    private readonly TaskFileDomainService $taskFileDomainService, 
    private readonly TaskFileVersionDomainService $taskFileVersionDomainService, LoggerFactory $loggerFactory ) 
{
 $this->logger = $loggerFactory->get(get_class($this)); 
}
 /** * CreateFileVersion. * * @param CreateFileVersionRequestDTO $requestDTO RequestDTO * @return CreateFileVersionResponseDTO CreateResult */ 
    public function createFileVersion( CreateFileVersionRequestDTO $requestDTO ): CreateFileVersionResponseDTO 
{
 // Getuser Authorizeinfo $fileKey = $requestDTO->getFileKey(); $editType = $requestDTO->getEditType(); $this->logger->info('Creating file version', [ 'file_key' => $fileKey, 'edit_type' => $editType, ]); // Validate Filewhether Exist $fileEntity = $this->taskFileDomainService->getByFileKey($fileKey); if (! $fileEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::FILE_NOT_FOUND, 'file.file_not_found'); 
}
 // Validate Filewhether as Directory if ($fileEntity->getIsDirectory()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::FILE_PERMISSION_DENIED, 'file.cannot_version_directory'); 
}
 $projectEntity = $this->projectDomainService->getProjectNotuser Id($fileEntity->getProjectId()); if (! $projectEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::PROJECT_NOT_FOUND, 'project.project_not_found'); 
}
 // call Domain ServiceCreateVersion $versionEntity = $this->taskFileVersionDomainService->createFileVersion($projectEntity->getuser OrganizationCode(), $fileEntity, $editType); if (! $versionEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::FILE_SAVE_FAILED, 'file.version_create_failed'); 
}
 $this->logger->info('File version created successfully', [ 'file_key' => $fileKey, 'file_id' => $fileEntity->getFileId(), 'version_id' => $versionEntity->getId(), 'version' => $versionEntity->getVersion(), 'edit_type' => $editType, ]); // Return Result return CreateFileVersionResponseDTO::createEmpty(); 
}
 /** * PagingGetFileVersionlist . * * @param RequestContext $requestContext RequestContext * @param GetFileVersionsRequestDTO $requestDTO RequestDTO * @return GetFileVersionsResponseDTO query Result */ 
    public function getFileVersions( RequestContext $requestContext, GetFileVersionsRequestDTO $requestDTO ): GetFileVersionsResponseDTO 
{
 // Getuser Authorizeinfo $userAuthorization = $requestContext->getuser Authorization(); $dataIsolation = $this->createDataIsolation($userAuthorization); $fileId = $requestDTO->getFileId(); $this->logger->info('Getting file versions with pagination', [ 'file_id' => $fileId, 'page' => $requestDTO->getPage(), 'page_size' => $requestDTO->getPageSize(), 'user_id' => $dataIsolation->getcurrent user Id(), 'organization_code' => $dataIsolation->getcurrent OrganizationCode(), ]); // Validate Filewhether Exist $fileEntity = $this->taskFileDomainService->getById($fileId); if (! $fileEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::FILE_NOT_FOUND, 'file.file_not_found'); 
}
 // Validate Filepermission - EnsureFilebelongs to current Organization /*if ($fileEntity->getOrganizationCode() !== $dataIsolation->getcurrent OrganizationCode()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::FILE_PERMISSION_DENIED, 'file.access_denied'); 
}
*/ // Validate Itempermission if ($fileEntity->getProjectId() > 0) 
{
 $this->getAccessibleProject( $fileEntity->getProjectId(), $dataIsolation->getcurrent user Id(), $dataIsolation->getcurrent OrganizationCode() ); 
}
 // call Domain ServiceGetPagingData $result = $this->taskFileVersionDomainService->getFileVersionsWithPage( $fileId, $requestDTO->getPage(), $requestDTO->getPageSize() ); $this->logger->info('File versions retrieved successfully', [ 'file_id' => $fileId, 'total' => $result['total'], 'current_page_count' => count($result['list']), ]); // Return Result return GetFileVersionsResponseDTO::fromData($result['list'], $result['total'], $requestDTO->getPage()); 
}
 /** * FileRollbackspecified Version. * * @param RequestContext $requestContext RequestContext * @param RollbackFileToVersionRequestDTO $requestDTO RequestDTO * @return RollbackFileToVersionResponseDTO RollbackResult */ 
    public function rollbackFileToVersion( RequestContext $requestContext, RollbackFileToVersionRequestDTO $requestDTO ): RollbackFileToVersionResponseDTO 
{
 $userAuthorization = $requestContext->getuser Authorization(); $dataIsolation = $this->createDataIsolation($userAuthorization); $fileId = $requestDTO->getFileId(); $targetVersion = $requestDTO->getVersion(); $this->logger->info('Rolling back file to version', [ 'file_id' => $fileId, 'target_version' => $targetVersion, 'user_id' => $dataIsolation->getcurrent user Id(), 'organization_code' => $dataIsolation->getcurrent OrganizationCode(), ]); // Validate Filewhether Exist $fileEntity = $this->taskFileDomainService->getById($fileId); if (! $fileEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::FILE_NOT_FOUND, 'file.file_not_found'); 
}
 // Validate Itempermission $projectEntity = $this->getAccessibleProject( $fileEntity->getProjectId(), $dataIsolation->getcurrent user Id(), $dataIsolation->getcurrent OrganizationCode() ); // Validate Filewhether as Directory if ($fileEntity->getIsDirectory()) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::FILE_PERMISSION_DENIED, 'file.cannot_rollback_directory'); 
}
 $newVersionEntity = $this->taskFileVersionDomainService->rollbackFileToVersion( $projectEntity->getuser OrganizationCode(), $fileEntity, $targetVersion ); if (! $newVersionEntity) 
{
 ExceptionBuilder::throw(SuperAgentErrorCode::FILE_SAVE_FAILED, 'file.rollback_failed'); 
}
 $this->logger->info('File rollback completed successfully', [ 'file_id' => $fileId, 'target_version' => $targetVersion, 'new_version_id' => $newVersionEntity->getId(), 'new_version' => $newVersionEntity->getVersion(), ]); return RollbackFileToVersionResponseDTO::createEmpty(); 
}
 
}
 
