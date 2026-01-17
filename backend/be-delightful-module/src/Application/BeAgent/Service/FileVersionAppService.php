<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Util\Context\RequestContext;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileVersionDomainService;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\CreateFileVersionRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\GetFileVersionsRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\RollbackFileToVersionRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\CreateFileVersionResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\GetFileVersionsResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\RollbackFileToVersionResponseDTO;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

class FileVersionAppService extends AbstractAppService
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly ProjectDomainService $projectDomainService,
        private readonly TaskFileDomainService $taskFileDomainService,
        private readonly TaskFileVersionDomainService $taskFileVersionDomainService,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    /**
     * Create file version.
     *
     * @param CreateFileVersionRequestDTO $requestDTO Request DTO
     * @return CreateFileVersionResponseDTO Create result
     */
    public function createFileVersion(
        CreateFileVersionRequestDTO $requestDTO
    ): CreateFileVersionResponseDTO {
        // Get user authorization information
        $fileKey = $requestDTO->getFileKey();
        $editType = $requestDTO->getEditType();

        $this->logger->info('Creating file version', [
            'file_key' => $fileKey,
            'edit_type' => $editType,
        ]);

        // Verify file exists
        $fileEntity = $this->taskFileDomainService->getByFileKey($fileKey);
        if (! $fileEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_NOT_FOUND, 'file.file_not_found');
        }

        // Verify file is not a directory
        if ($fileEntity->getIsDirectory()) {
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_PERMISSION_DENIED, 'file.cannot_version_directory');
        }

        $projectEntity = $this->projectDomainService->getProjectNotUserId($fileEntity->getProjectId());
        if (! $projectEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::PROJECT_NOT_FOUND, 'project.project_not_found');
        }

        // Call Domain Service to create version
        $versionEntity = $this->taskFileVersionDomainService->createFileVersion($projectEntity->getUserOrganizationCode(), $fileEntity, $editType);

        if (! $versionEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_SAVE_FAILED, 'file.version_create_failed');
        }

        $this->logger->info('File version created successfully', [
            'file_key' => $fileKey,
            'file_id' => $fileEntity->getFileId(),
            'version_id' => $versionEntity->getId(),
            'version' => $versionEntity->getVersion(),
            'edit_type' => $editType,
        ]);

        // Return result
        return CreateFileVersionResponseDTO::createEmpty();
    }

    /**
     * Get file version list with pagination.
     *
     * @param RequestContext $requestContext Request context
     * @param GetFileVersionsRequestDTO $requestDTO Request DTO
     * @return GetFileVersionsResponseDTO Query result
     */
    public function getFileVersions(
        RequestContext $requestContext,
        GetFileVersionsRequestDTO $requestDTO
    ): GetFileVersionsResponseDTO {
        // Get user authorization information
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $fileId = $requestDTO->getFileId();

        $this->logger->info('Getting file versions with pagination', [
            'file_id' => $fileId,
            'page' => $requestDTO->getPage(),
            'page_size' => $requestDTO->getPageSize(),
            'user_id' => $dataIsolation->getCurrentUserId(),
            'organization_code' => $dataIsolation->getCurrentOrganizationCode(),
        ]);

        // Verify file exists
        $fileEntity = $this->taskFileDomainService->getById($fileId);
        if (! $fileEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_NOT_FOUND, 'file.file_not_found');
        }

        // Verify file permission - ensure file belongs to current organization
        /*if ($fileEntity->getOrganizationCode() !== $dataIsolation->getCurrentOrganizationCode()) {
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_PERMISSION_DENIED, 'file.access_denied');
        }*/

        // Verify project permission
        if ($fileEntity->getProjectId() > 0) {
            $this->getAccessibleProject(
                $fileEntity->getProjectId(),
                $dataIsolation->getCurrentUserId(),
                $dataIsolation->getCurrentOrganizationCode()
            );
        }

        // Call Domain Service to get paginated data
        $result = $this->taskFileVersionDomainService->getFileVersionsWithPage(
            $fileId,
            $requestDTO->getPage(),
            $requestDTO->getPageSize()
        );

        $this->logger->info('File versions retrieved successfully', [
            'file_id' => $fileId,
            'total' => $result['total'],
            'current_page_count' => count($result['list']),
        ]);

        // Return result
        return GetFileVersionsResponseDTO::fromData($result['list'], $result['total'], $requestDTO->getPage());
    }

    /**
     * Rollback file to specified version.
     *
     * @param RequestContext $requestContext Request context
     * @param RollbackFileToVersionRequestDTO $requestDTO Request DTO
     * @return RollbackFileToVersionResponseDTO Rollback result
     */
    public function rollbackFileToVersion(
        RequestContext $requestContext,
        RollbackFileToVersionRequestDTO $requestDTO
    ): RollbackFileToVersionResponseDTO {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $fileId = $requestDTO->getFileId();
        $targetVersion = $requestDTO->getVersion();

        $this->logger->info('Rolling back file to version', [
            'file_id' => $fileId,
            'target_version' => $targetVersion,
            'user_id' => $dataIsolation->getCurrentUserId(),
            'organization_code' => $dataIsolation->getCurrentOrganizationCode(),
        ]);

        // Verify file exists
        $fileEntity = $this->taskFileDomainService->getById($fileId);
        if (! $fileEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_NOT_FOUND, 'file.file_not_found');
        }

        // Verify project permission
        $projectEntity = $this->getAccessibleProject(
            $fileEntity->getProjectId(),
            $dataIsolation->getCurrentUserId(),
            $dataIsolation->getCurrentOrganizationCode()
        );

        // Verify file is not a directory
        if ($fileEntity->getIsDirectory()) {
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_PERMISSION_DENIED, 'file.cannot_rollback_directory');
        }

        $newVersionEntity = $this->taskFileVersionDomainService->rollbackFileToVersion(
            $projectEntity->getUserOrganizationCode(),
            $fileEntity,
            $targetVersion
        );

        if (! $newVersionEntity) {
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_SAVE_FAILED, 'file.rollback_failed');
        }

        $this->logger->info('File rollback completed successfully', [
            'file_id' => $fileId,
            'target_version' => $targetVersion,
            'new_version_id' => $newVersionEntity->getId(),
            'new_version' => $newVersionEntity->getVersion(),
        ]);

        return RollbackFileToVersionResponseDTO::createEmpty();
    }
}
