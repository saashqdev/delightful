<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\BeAgent\Service;

use App\Application\File\Service\FileAppService;
use App\Domain\Contact\Entity\ValueObject\DataIsolation;
use App\Domain\File\Repository\Persistence\Facade\CloudFileRepositoryInterface;
use App\ErrorCode\GenericErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use App\Infrastructure\Util\Context\RequestContext;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use App\Infrastructure\Util\Locker\LockerInterface;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use Delightful\BeDelightful\Application\BeAgent\Event\Publish\FileBatchCopyPublisher;
use Delightful\BeDelightful\Application\BeAgent\Event\Publish\FileBatchMovePublisher;
use Delightful\BeDelightful\Domain\Share\Constant\ResourceType;
use Delightful\BeDelightful\Domain\Share\Service\ResourceShareDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\StorageType;
use Delightful\BeDelightful\Domain\BeAgent\Event\DirectoryDeletedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\FileBatchCopyEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\FileBatchMoveEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\FileDeletedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\FileMovedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\FileRenamedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\FileReplacedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\FilesBatchDeletedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Event\FileUploadedEvent;
use Delightful\BeDelightful\Domain\BeAgent\Service\ProjectDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TaskFileVersionDomainService;
use Delightful\BeDelightful\Domain\BeAgent\Service\TopicDomainService;
use Delightful\BeDelightful\ErrorCode\ShareErrorCode;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;
use Delightful\BeDelightful\Infrastructure\Utils\AccessTokenUtil;
use Delightful\BeDelightful\Infrastructure\Utils\FileBatchOperationStatusManager;
use Delightful\BeDelightful\Infrastructure\Utils\WorkDirectoryUtil;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\BatchCopyFileRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\BatchDeleteFilesRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\BatchMoveFileRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\BatchSaveProjectFilesRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\CheckBatchOperationStatusRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\CreateFileRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\DeleteDirectoryRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\ProjectUploadTokenRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\ReplaceFileRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\SaveProjectFileRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request\TopicUploadTokenRequestDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\FileBatchOperationResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\FileBatchOperationStatusResponseDTO;
use Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response\TaskFileItemDTO;
use Hyperf\Amqp\Producer;
use Hyperf\DbConnection\Db;
use Hyperf\Logger\LoggerFactory;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Translation\trans;

class FileManagementAppService extends AbstractAppService
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private readonly FileAppService $fileAppService,
        private readonly TopicDomainService $topicDomainService,
        private readonly TaskFileDomainService $taskFileDomainService,
        private readonly TaskFileVersionDomainService $taskFileVersionDomainService,
        private readonly CloudFileRepositoryInterface $cloudFileRepository,
        private readonly ResourceShareDomainService $resourceShareDomainService,
        private readonly FileBatchOperationStatusManager $batchOperationStatusManager,
        private readonly LockerInterface $locker,
        private readonly Producer $producer,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ProjectDomainService $projectDomainService,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    /**
     * Get project file upload STS Token.
     *
     * @param ProjectUploadTokenRequestDTO $requestDTO Request DTO
     * @return array Get result
     */
    public function getProjectUploadToken(RequestContext $requestContext, ProjectUploadTokenRequestDTO $requestDTO): array
    {
        try {
            $projectId = $requestDTO->getProjectId();
            $expires = $requestDTO->getExpires();

            // Get current user information
            $userAuthorization = $requestContext->getUserAuthorization();

            // Create data isolation object
            $dataIsolation = $this->createDataIsolation($userAuthorization);
            $userId = $dataIsolation->getCurrentUserId();
            $organizationCode = $dataIsolation->getCurrentOrganizationCode();

            // Case 1: Has project ID, get project's work_dir
            if (! empty($projectId)) {
                $projectEntity = $this->getAccessibleProject((int) $projectId, $userId, $userAuthorization->getOrganizationCode());
                $workDir = $projectEntity->getWorkDir();
                if (empty($workDir)) {
                    ExceptionBuilder::throw(BeAgentErrorCode::WORK_DIR_NOT_FOUND, trans('project.work_dir.not_found'));
                }
                $organizationCode = $projectEntity->getUserOrganizationCode();
            } else {
                // Case 2: No project ID, use snowflake ID to generate temporary project ID
                $tempProjectId = IdGenerator::getSnowId();
                $workDir = WorkDirectoryUtil::getWorkDir($userId, $tempProjectId);
            }

            // Get STS Token
            $userAuthorization = new DelightfulUserAuthorization();
            $userAuthorization->setOrganizationCode($organizationCode);
            $storageType = StorageBucketType::SandBox->value;

            return $this->fileAppService->getStsTemporaryCredentialV2(
                $organizationCode,
                $storageType,
                $workDir,
                $expires,
                false,
            );
        } catch (BusinessException $e) {
            // Catch business exception (thrown by ExceptionBuilder::throw)
            $this->logger->warning(sprintf(
                'Business logic error in get project upload token: %s, Project ID: %s, Error Code: %d',
                $e->getMessage(),
                $requestDTO->getProjectId(),
                $e->getCode()
            ));
            // Re-throw business exception directly, let upper layer handle it
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'System error in get project upload token: %s, Project ID: %s',
                $e->getMessage(),
                $requestDTO->getProjectId()
            ));
            ExceptionBuilder::throw(GenericErrorCode::SystemError, trans('system.upload_token_failed'));
        }
    }

    /**
     * Get topic file upload STS Token.
     *
     * @param RequestContext $requestContext Request context
     * @param TopicUploadTokenRequestDTO $requestDTO Request DTO
     * @return array Get result
     */
    public function getTopicUploadToken(RequestContext $requestContext, TopicUploadTokenRequestDTO $requestDTO): array
    {
        try {
            $topicId = $requestDTO->getTopicId();
            $expires = $requestDTO->getExpires();

            // Get current user information
            $userAuthorization = $requestContext->getUserAuthorization();

            // Create data isolation object
            $dataIsolation = $this->createDataIsolation($userAuthorization);
            $userId = $dataIsolation->getCurrentUserId();
            $organizationCode = $dataIsolation->getCurrentOrganizationCode();

            // Generate topic working directory
            $topicEntity = $this->topicDomainService->getTopicById((int) $topicId);
            if (empty($topicEntity)) {
                ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, trans('topic.not_found'));
            }
            $projectEntity = $this->projectDomainService->getProjectNotUserId($topicEntity->getProjectId());
            $workDir = WorkDirectoryUtil::getTopicUploadDir($userId, $topicEntity->getProjectId(), $topicEntity->getId());

            // Get STS Token
            $userAuthorization = new DelightfulUserAuthorization();
            $userAuthorization->setOrganizationCode($organizationCode);
            $storageType = StorageBucketType::SandBox->value;

            return $this->fileAppService->getStsTemporaryCredentialV2(
                $projectEntity->getUserOrganizationCode(),
                $storageType,
                $workDir,
                $expires,
            );
        } catch (BusinessException $e) {
            // Catch business exception (thrown by ExceptionBuilder::throw)
            $this->logger->warning(sprintf(
                'Business logic error in get topic upload token: %s, Topic ID: %s, Error Code: %d',
                $e->getMessage(),
                $requestDTO->getTopicId(),
                $e->getCode()
            ));
            // Re-throw business exception directly, let upper layer handle it
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'System error in get topic upload token: %s, Topic ID: %s',
                $e->getMessage(),
                $requestDTO->getTopicId()
            ));
            ExceptionBuilder::throw(GenericErrorCode::SystemError, trans('system.upload_token_failed'));
        }
    }

    /**
     * Save project file.
     *
     * @param RequestContext $requestContext Request context
     * @param SaveProjectFileRequestDTO $requestDTO Request DTO
     * @return array Save result
     */
    public function saveFile(RequestContext $requestContext, SaveProjectFileRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        // Build lock name - based on project ID and relative directory path
        $projectId = $requestDTO->getProjectId();
        $fileKey = $requestDTO->getFileKey();

        // Validate project ownership and get working directory - need to get project info first
        $projectEntity = $this->getAccessibleProjectWithEditor((int) $requestDTO->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

        $lockName = WorkDirectoryUtil::getLockerKey($projectEntity->getId());
        $lockOwner = $dataIsolation->getCurrentUserId();

        // Acquire spin lock (30 seconds timeout)
        if (! $this->locker->spinLock($lockName, $lockOwner, 30)) {
            ExceptionBuilder::throw(
                BeAgentErrorCode::FILE_SAVE_FAILED,
                trans('file.directory_creation_locked')
            );
        }

        if (empty($requestDTO->getFileKey())) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, trans('validation.file_key_required'));
        }

        if (empty($requestDTO->getFileName())) {
            ExceptionBuilder::throw(GenericErrorCode::ParameterMissing, trans('validation.file_name_required'));
        }

        Db::beginTransaction();
        try {
            if (empty($requestDTO->getParentId())) {
                $parentId = $this->taskFileDomainService->findOrCreateDirectoryAndGetParentId(
                    projectId: (int) $projectId,
                    userId: $dataIsolation->getCurrentUserId(),
                    organizationCode: $dataIsolation->getCurrentOrganizationCode(),
                    projectOrganizationCode: $projectEntity->getUserOrganizationCode(),
                    fullFileKey: $requestDTO->getFileKey(),
                    workDir: $projectEntity->getWorkDir(),
                );
                $requestDTO->setParentId((string) $parentId);
            } else {
                $parentFileEntity = $this->taskFileDomainService->getById((int) $requestDTO->getParentId());
                if (empty($parentFileEntity) || $parentFileEntity->getProjectId() != (int) $projectId) {
                    ExceptionBuilder::throw(BeAgentErrorCode::FILE_NOT_FOUND, trans('file.not_found'));
                }
            }

            // Create TaskFileEntity entity
            $taskFileEntity = $requestDTO->toEntity();

            // Calculate sort value through domain service
            $sortValue = $this->taskFileDomainService->calculateSortForNewFile(
                ! empty($requestDTO->getParentId()) ? (int) $requestDTO->getParentId() : null,
                (int) $requestDTO->getPreFileId(),
                (int) $requestDTO->getProjectId()
            );

            // Set sort value
            $taskFileEntity->setSort($sortValue);

            // Call domain service to save file
            $savedEntity = $this->taskFileDomainService->saveProjectFile(
                $dataIsolation,
                $projectEntity,
                $taskFileEntity,
                StorageType::WORKSPACE->value
            );

            Db::commit();

            // Publish file uploaded event
            $fileUploadedEvent = new FileUploadedEvent($taskFileEntity, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());
            $this->eventDispatcher->dispatch($fileUploadedEvent);

            // Return save result
            return TaskFileItemDTO::fromEntity($savedEntity, $projectEntity->getWorkDir())->toArray();
        } catch (BusinessException $e) {
            // Catch business exception (thrown by ExceptionBuilder::throw)
            Db::rollBack();
            $this->logger->warning(sprintf(
                'Business logic error in save file: %s, Project ID: %s, File Key: %s, Error Code: %d',
                $e->getMessage(),
                $requestDTO->getProjectId(),
                $requestDTO->getFileKey(),
                $e->getCode()
            ));
            // Re-throw business exception directly, let upper layer handle it
            throw $e;
        } catch (Throwable $e) {
            Db::rollBack();
            $this->logger->error(sprintf(
                'System error in save project file: %s, Project ID: %s, File Key: %s',
                $e->getMessage(),
                $requestDTO->getProjectId(),
                $requestDTO->getFileKey()
            ));
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_SAVE_FAILED, trans('file.file_save_failed'));
        } finally {
            // Ensure lock is released
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * Batch save project files (in the same directory).
     *
     * @param RequestContext $requestContext Request context
     * @param BatchSaveProjectFilesRequestDTO $requestDTO Batch save request DTO
     * @return array Batch save result, returns file ID array
     */
    public function batchSaveFiles(RequestContext $requestContext, BatchSaveProjectFilesRequestDTO $requestDTO): array
    {
        $files = $requestDTO->getFiles();

        if (empty($files)) {
            return [];
        }

        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $projectId = (int) $requestDTO->getProjectId();

        // Project-level lock
        $lockName = WorkDirectoryUtil::getLockerKey($projectId);
        $lockOwner = $userAuthorization->getId();

        // Acquire project-level lock (30 seconds timeout)
        if (! $this->locker->spinLock($lockName, $lockOwner, 30)) {
            ExceptionBuilder::throw(
                BeAgentErrorCode::FILE_SAVE_FAILED,
                trans('file.batch_save_locked')
            );
        }

        // 1. Validate project permissions
        $projectEntity = $this->getAccessibleProjectWithEditor($projectId, $dataIsolation->getCurrentUserId(), $dataIsolation->getCurrentOrganizationCode());

        Db::beginTransaction();
        try {
            // 3. Batch save files
            $savedFileIds = [];
            foreach ($files as $fileData) {
                try {
                    // Basic parameter validation
                    if (empty($fileData['file_key']) || empty($fileData['file_name'])) {
                        continue;
                    }

                    // Create SaveProjectFileRequestDTO
                    $fileData['project_id'] = (string) $projectEntity->getId();
                    $fileData['parent_id'] = '';
                    $requestDTO = SaveProjectFileRequestDTO::fromRequest($fileData);

                    // Create file entity
                    $taskFileEntity = $requestDTO->toEntity();

                    // Save file (without setting sort value)
                    $savedEntity = $this->taskFileDomainService->saveProjectFile(
                        $dataIsolation,
                        $projectEntity,
                        $taskFileEntity,
                        StorageType::WORKSPACE->value
                    );

                    $savedFileIds[] = TaskFileItemDTO::fromEntity($savedEntity, $projectEntity->getWorkDir());
                } catch (Throwable $e) {
                    $this->logger->warning(sprintf(
                        'Single file save failed in batch: %s, File: %s, Error: %s',
                        $fileData['file_key'] ?? 'unknown',
                        $fileData['file_name'] ?? 'unknown',
                        $e->getMessage()
                    ));
                    // Single file failure does not affect other files, continue processing next one
                }
            }
            Db::commit();
            return $savedFileIds;
        } catch (BusinessException $e) {
            Db::rollBack();
            $this->logger->warning(sprintf(
                'Business logic error in batch save files: %s, Project ID: %s, Error Code: %d',
                $e->getMessage(),
                $projectId,
                $e->getCode()
            ));
            throw $e;
        } catch (Throwable $e) {
            Db::rollBack();
            $this->logger->error(sprintf(
                'System error in batch save files: %s, Project ID: %s',
                $e->getMessage(),
                $projectId
            ));
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_SAVE_FAILED, trans('file.batch_save_failed'));
        } finally {
            // Ensure lock is released
            $this->locker->release($lockName, $lockOwner);
        }
    }

    /**
     * Create file or folder.
     *
     * @param RequestContext $requestContext Request context
     * @param CreateFileRequestDTO $requestDTO Request DTO
     * @return array Create result
     */
    public function createFile(RequestContext $requestContext, CreateFileRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        Db::beginTransaction();
        try {
            $projectId = (int) $requestDTO->getProjectId();
            $parentId = ! empty($requestDTO->getParentId()) ? (int) $requestDTO->getParentId() : 0;

            // Validate project ownership - ensure users can only create files in their own projects
            $projectEntity = $this->getAccessibleProjectWithEditor($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

            // If parent_id is empty, set to root directory
            if (empty($parentId)) {
                $parentId = $this->taskFileDomainService->findOrCreateProjectRootDirectory(
                    projectId: $projectId,
                    workDir: $projectEntity->getWorkDir(),
                    userId: $dataIsolation->getCurrentUserId(),
                    organizationCode: $dataIsolation->getCurrentOrganizationCode(),
                    projectOrganizationCode: $projectEntity->getUserOrganizationCode()
                );
            }

            // Calculate sort value through domain service
            $sortValue = $this->taskFileDomainService->calculateSortForNewFile(
                $parentId === 0 ? null : $parentId,
                (int) $requestDTO->getPreFileId(),
                $projectId
            );

            // Call domain service to create file or folder
            $taskFileEntity = $this->taskFileDomainService->createProjectFile(
                $dataIsolation,
                $projectEntity,
                $parentId,
                $requestDTO->getFileName(),
                $requestDTO->getIsDirectory(),
                $sortValue
            );

            Db::commit();

            // Publish file uploaded event
            $fileUploadedEvent = new FileUploadedEvent($taskFileEntity, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());
            $this->eventDispatcher->dispatch($fileUploadedEvent);

            // Return create result
            return TaskFileItemDTO::fromEntity($taskFileEntity, $projectEntity->getWorkDir())->toArray();
        } catch (BusinessException $e) {
            // Catch business exception (thrown by ExceptionBuilder::throw)
            Db::rollBack();
            $this->logger->warning(sprintf(
                'Business logic error in create file: %s, Project ID: %s, File Name: %s, Error Code: %d',
                $e->getMessage(),
                $requestDTO->getProjectId(),
                $requestDTO->getFileName(),
                $e->getCode()
            ));
            // Re-throw business exception directly, let upper layer handle it
            throw $e;
        } catch (Throwable $e) {
            Db::rollBack();
            $this->logger->error(sprintf(
                'System error in create file: %s, Project ID: %s, File Name: %s',
                $e->getMessage(),
                $requestDTO->getProjectId(),
                $requestDTO->getFileName()
            ));
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_CREATE_FAILED, trans('file.file_create_failed'));
        }
    }

    public function deleteFile(RequestContext $requestContext, int $fileId): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        try {
            $fileEntity = $this->taskFileDomainService->getUserFileEntityNoUser($fileId);
            $projectEntity = $this->getAccessibleProjectWithEditor($fileEntity->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode());
            if ($fileEntity->getIsDirectory()) {
                $deletedCount = $this->taskFileDomainService->deleteDirectoryFiles($dataIsolation, $projectEntity->getWorkDir(), $projectEntity->getId(), $fileEntity->getFileKey(), $projectEntity->getUserOrganizationCode());
                // Publish directory deleted event
                $directoryDeletedEvent = new DirectoryDeletedEvent($fileEntity, $userAuthorization);
                $this->eventDispatcher->dispatch($directoryDeletedEvent);
            } else {
                $deletedCount = 1;
                $this->taskFileDomainService->deleteProjectFiles($projectEntity->getUserOrganizationCode(), $fileEntity, $projectEntity->getWorkDir());
                // Publish file deleted event
                $fileDeletedEvent = new FileDeletedEvent($fileEntity, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());
                $this->eventDispatcher->dispatch($fileDeletedEvent);
            }
            return ['file_id' => $fileId, 'count' => $deletedCount];
        } catch (BusinessException $e) {
            // Catch business exception (thrown by ExceptionBuilder::throw)
            $this->logger->warning(sprintf(
                'Business logic error in delete file: %s, File ID: %s, Error Code: %d',
                $e->getMessage(),
                $fileId,
                $e->getCode()
            ));
            // Re-throw business exception directly, let upper layer handle it
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'System error in delete project file: %s, File ID: %s',
                $e->getMessage(),
                $fileId
            ));
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_DELETE_FAILED, trans('file.file_delete_failed'));
        }
    }

    public function deleteDirectory(RequestContext $requestContext, DeleteDirectoryRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);
        $userId = $dataIsolation->getCurrentUserId();

        try {
            $projectId = (int) $requestDTO->getProjectId();
            $fileId = $requestDTO->getFileId();

            // 1. Verify project belongs to current user
            $projectEntity = $this->getAccessibleProjectWithEditor($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

            // 2. Get working directory and build full path
            $workDir = $projectEntity->getWorkDir();
            if (empty($workDir)) {
                ExceptionBuilder::throw(BeAgentErrorCode::WORK_DIR_NOT_FOUND, trans('project.work_dir.not_found'));
            }

            $fileEntity = $this->taskFileDomainService->getById((int) $fileId);
            if (empty($fileEntity) || $fileEntity->getProjectId() != $projectId) {
                ExceptionBuilder::throw(BeAgentErrorCode::FILE_NOT_FOUND, trans('file.file_not_found'));
            }

            // 3. Build target deletion path
            $targetPath = $fileEntity->getFileKey();

            // 4. Call domain service to execute batch deletion
            $deletedCount = $this->taskFileDomainService->deleteDirectoryFiles($dataIsolation, $workDir, $projectId, $targetPath, $projectEntity->getUserOrganizationCode());

            // Publish directory deleted event
            $directoryDeletedEvent = new DirectoryDeletedEvent($fileEntity, $userAuthorization);
            $this->eventDispatcher->dispatch($directoryDeletedEvent);

            $this->logger->info(sprintf(
                'Successfully deleted directory: Project ID: %s, Path: %s, Deleted files: %d',
                $projectId,
                $targetPath,
                $deletedCount
            ));

            return [
                'project_id' => $projectId,
                'deleted_count' => $deletedCount,
            ];
        } catch (BusinessException $e) {
            // Catch business exception (thrown by ExceptionBuilder::throw)
            $this->logger->warning(sprintf(
                'Business logic error in delete directory: %s, Project ID: %s, File ID: %s, Error Code: %d',
                $e->getMessage(),
                $requestDTO->getProjectId(),
                $requestDTO->getFileId(),
                $e->getCode()
            ));
            // Re-throw business exception directly, let upper layer handle it
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'System error in delete directory: %s, Project ID: %s, File ID: %s',
                $e->getMessage(),
                $requestDTO->getProjectId(),
                $requestDTO->getFileId()
            ));
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_DELETE_FAILED, trans('file.directory_delete_failed'));
        }
    }

    public function batchDeleteFiles(RequestContext $requestContext, BatchDeleteFilesRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        try {
            $projectId = (int) $requestDTO->getProjectId();
            $fileIds = $requestDTO->getFileIds();
            $forceDelete = $requestDTO->getForceDelete();

            // Validate project ownership
            $projectEntity = $this->getAccessibleProjectWithEditor($projectId, $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

            // Call domain service to batch delete files
            $result = $this->taskFileDomainService->batchDeleteProjectFiles(
                $dataIsolation,
                $projectEntity->getWorkDir(),
                $projectId,
                $fileIds,
                $forceDelete,
                $projectEntity->getUserOrganizationCode()
            );

            $this->logger->info(sprintf(
                'Successfully batch deleted files: Project ID: %s, File count: %d',
                $projectId,
                count($fileIds)
            ));

            // Publish file uploaded event
            $fileUploadedEvent = new FilesBatchDeletedEvent((int) $requestDTO->getProjectId(), $requestDTO->getFileIds(), $userAuthorization);
            $this->eventDispatcher->dispatch($fileUploadedEvent);

            return $result;
        } catch (BusinessException $e) {
            // Catch business exception (thrown by ExceptionBuilder::throw)
            $this->logger->warning(sprintf(
                'Business logic error in batch delete files: %s, Project ID: %s, File IDs: %s, Error Code: %d',
                $e->getMessage(),
                $requestDTO->getProjectId(),
                implode(',', $requestDTO->getFileIds()),
                $e->getCode()
            ));
            // Re-throw business exception directly, let upper layer handle it
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'System error in batch delete files: %s, Project ID: %s, File IDs: %s',
                $e->getMessage(),
                $requestDTO->getProjectId(),
                implode(',', $requestDTO->getFileIds())
            ));
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_DELETE_FAILED, trans('file.batch_delete_failed'));
        }
    }

    public function renameFile(RequestContext $requestContext, int $fileId, string $targetName): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        try {
            $fileEntity = $this->taskFileDomainService->getUserFileEntityNoUser($fileId);
            $projectEntity = $this->getAccessibleProjectWithEditor($fileEntity->getProjectId(), $userAuthorization->getId(), $userAuthorization->getOrganizationCode());

            if ($fileEntity->getIsDirectory()) {
                // Directory rename: batch process all sub-files
                $renamedCount = $this->taskFileDomainService->renameDirectoryFiles(
                    $dataIsolation,
                    $fileEntity,
                    $projectEntity,
                    $targetName
                );
                // Get the updated entity after rename
                $newFileEntity = $this->taskFileDomainService->getById($fileId);
            } else {
                // Single file rename: use existing method
                $newFileEntity = $this->taskFileDomainService->renameProjectFile($dataIsolation, $fileEntity, $projectEntity, $targetName);
            }

            // Publish file renamed event
            $fileRenamedEvent = new FileRenamedEvent($newFileEntity, $userAuthorization);
            $this->eventDispatcher->dispatch($fileRenamedEvent);

            return TaskFileItemDTO::fromEntity($newFileEntity, $projectEntity->getWorkDir())->toArray();
        } catch (BusinessException $e) {
            // Catch business exception (thrown by ExceptionBuilder::throw)
            $this->logger->warning(sprintf(
                'Business logic error in rename file: %s, File ID: %s, Error Code: %d',
                $e->getMessage(),
                $fileId,
                $e->getCode()
            ));
            // Re-throw business exception directly, let upper layer handle it
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'System error in rename project file: %s, File ID: %s',
                $e->getMessage(),
                $fileId
            ));
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_RENAME_FAILED, trans('file.file_rename_failed'));
        }
    }

    /**
     * Move file to target directory (supports both same-project and cross-project move).
     *
     * @param RequestContext $requestContext Request context
     * @param int $fileId File ID to move
     * @param int $targetParentId Target parent directory ID
     * @param null|int $preFileId Previous file ID for positioning
     * @param null|int $targetProjectId Target project ID (null means same project)
     * @param array $keepBothFileIds Array of source file IDs that should not overwrite when conflict occurs
     * @return array Move result
     */
    public function moveFile(
        RequestContext $requestContext,
        int $fileId,
        int $targetParentId,
        ?int $preFileId = null,
        ?int $targetProjectId = null,
        array $keepBothFileIds = []
    ): array {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        try {
            // 1. Get source file entity
            $fileEntity = $this->taskFileDomainService->getUserFileEntityNoUser($fileId);

            // 2. Get source project and verify permission
            $sourceProject = $this->getAccessibleProjectWithEditor(
                $fileEntity->getProjectId(),
                $userAuthorization->getId(),
                $userAuthorization->getOrganizationCode()
            );

            // 3. Get target project (if not provided, use source project)
            $targetProject = $targetProjectId
                ? $this->getAccessibleProjectWithEditor(
                    $targetProjectId,
                    $userAuthorization->getId(),
                    $userAuthorization->getOrganizationCode()
                )
                : $sourceProject;

            // 4. Handle target parent directory
            if (empty($targetParentId)) {
                $targetParentId = $this->taskFileDomainService->findOrCreateProjectRootDirectory(
                    projectId: $targetProject->getId(),
                    workDir: $targetProject->getWorkDir(),
                    userId: $dataIsolation->getCurrentUserId(),
                    organizationCode: $dataIsolation->getCurrentOrganizationCode(),
                    projectOrganizationCode: $targetProject->getUserOrganizationCode()
                );
            }

            // 5. Directory move: use asynchronous processing
            if ($fileEntity->getIsDirectory()) {
                $batchKey = $this->batchOperationStatusManager->generateBatchKey(
                    FileBatchOperationStatusManager::OPERATION_MOVE,
                    $dataIsolation->getCurrentUserId(),
                    (string) $fileEntity->getFileId()
                );

                // Initialize task status
                $this->batchOperationStatusManager->initializeTask(
                    $batchKey,
                    FileBatchOperationStatusManager::OPERATION_MOVE,
                    $dataIsolation->getCurrentUserId(),
                    1
                );

                // Publish move event
                $fileIds = $this->taskFileDomainService->getDirectoryFileIds($dataIsolation, $fileEntity);
                $event = FileBatchMoveEvent::fromDTO(
                    $batchKey,
                    $dataIsolation->getCurrentUserId(),
                    $dataIsolation->getCurrentOrganizationCode(),
                    $fileIds,
                    $targetProject->getId(),
                    $sourceProject->getId(),
                    $preFileId,
                    $targetParentId,
                    $keepBothFileIds
                );

                $this->logger->info(sprintf('Move directory request data, batchKey: %s', $batchKey), [
                    'file_ids' => $fileIds,
                    'source_project_id' => $sourceProject->getId(),
                    'target_project_id' => $targetProject->getId(),
                    'target_parent_id' => $targetParentId,
                    'pre_file_id' => $preFileId,
                    'keep_both_file_ids' => $keepBothFileIds,
                ]);

                $publisher = new FileBatchMovePublisher($event);
                $this->producer->produce($publisher);

                // Return asynchronous response
                return FileBatchOperationResponseDTO::createAsyncProcessing($batchKey)->toArray();
            }

            // 6. Single file sync move
            // Handle file path update if needed
            $originalParentId = $fileEntity->getParentId();
            $needUpdatePath = ($sourceProject->getId() !== $targetProject->getId())
                           || ($originalParentId !== $targetParentId);

            if ($needUpdatePath) {
                $this->taskFileDomainService->moveProjectFile(
                    $dataIsolation,
                    $fileEntity,
                    $sourceProject,
                    $targetProject,
                    $targetParentId,
                    $keepBothFileIds
                );
            }

            // 7. Handle file sorting in target project
            $this->taskFileDomainService->handleFileSortOnMove(
                $fileEntity,
                $targetProject,
                $targetParentId,
                $preFileId
            );

            // 8. Re-get file entity with updated data
            $newFileEntity = $this->taskFileDomainService->getById($fileId);

            // 9. Dispatch file moved event
            $fileMovedEvent = new FileMovedEvent($newFileEntity, $userAuthorization);
            $this->eventDispatcher->dispatch($fileMovedEvent);

            $result = TaskFileItemDTO::fromEntity($newFileEntity)->toArray();
            return FileBatchOperationResponseDTO::createSyncSuccess($result)->toArray();
        } catch (BusinessException $e) {
            $this->logger->warning('Business logic error in move file', [
                'file_id' => $fileId,
                'source_project_id' => isset($sourceProject) ? $sourceProject->getId() : null,
                'target_project_id' => isset($targetProject) ? $targetProject->getId() : null,
                'target_parent_id' => $targetParentId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('System error in move file', [
                'file_id' => $fileId,
                'source_project_id' => isset($sourceProject) ? $sourceProject->getId() : null,
                'target_project_id' => isset($targetProject) ? $targetProject->getId() : null,
                'target_parent_id' => $targetParentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_MOVE_FAILED, trans('file.file_move_failed'));
        }
    }

    /**
     * Copy file to target directory (supports both same-project and cross-project copy).
     *
     * @param RequestContext $requestContext Request context
     * @param int $fileId File ID to copy
     * @param int $targetParentId Target parent directory ID
     * @param null|int $preFileId Previous file ID for positioning
     * @param null|int $targetProjectId Target project ID (null means same project)
     * @param array $keepBothFileIds Array of source file IDs that should not overwrite when conflict occurs
     * @return array Copy result
     */
    public function copyFile(
        RequestContext $requestContext,
        int $fileId,
        int $targetParentId,
        ?int $preFileId = null,
        ?int $targetProjectId = null,
        array $keepBothFileIds = []
    ): array {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        try {
            // 1. Get source file entity
            $fileEntity = $this->taskFileDomainService->getUserFileEntityNoUser($fileId);

            // 2. Get source project and verify permission
            $sourceProject = $this->getAccessibleProjectWithEditor(
                $fileEntity->getProjectId(),
                $userAuthorization->getId(),
                $userAuthorization->getOrganizationCode()
            );

            // 3. Get target project (if not provided, use source project)
            $targetProject = $targetProjectId
                ? $this->getAccessibleProjectWithEditor(
                    $targetProjectId,
                    $userAuthorization->getId(),
                    $userAuthorization->getOrganizationCode()
                )
                : $sourceProject;

            // 4. Handle target parent directory
            if (empty($targetParentId)) {
                $targetParentId = $this->taskFileDomainService->findOrCreateProjectRootDirectory(
                    projectId: $targetProject->getId(),
                    workDir: $targetProject->getWorkDir(),
                    userId: $dataIsolation->getCurrentUserId(),
                    organizationCode: $dataIsolation->getCurrentOrganizationCode(),
                    projectOrganizationCode: $targetProject->getUserOrganizationCode()
                );
            }

            // 5. Directory copy: use asynchronous processing
            if ($fileEntity->getIsDirectory()) {
                $batchKey = $this->batchOperationStatusManager->generateBatchKey(
                    FileBatchOperationStatusManager::OPERATION_COPY,
                    $dataIsolation->getCurrentUserId(),
                    (string) $fileEntity->getFileId()
                );

                // Initialize task status
                $this->batchOperationStatusManager->initializeTask(
                    $batchKey,
                    FileBatchOperationStatusManager::OPERATION_COPY,
                    $dataIsolation->getCurrentUserId(),
                    1
                );

                // Publish copy event
                $fileIds = $this->taskFileDomainService->getDirectoryFileIds($dataIsolation, $fileEntity);
                $event = FileBatchCopyEvent::fromDTO(
                    $batchKey,
                    $dataIsolation->getCurrentUserId(),
                    $dataIsolation->getCurrentOrganizationCode(),
                    $fileIds,
                    $targetProject->getId(),
                    $sourceProject->getId(),
                    $preFileId,
                    $targetParentId,
                    $keepBothFileIds
                );

                $this->logger->info(sprintf('Copy directory request data, batchKey: %s', $batchKey), [
                    'file_ids' => $fileIds,
                    'source_project_id' => $sourceProject->getId(),
                    'target_project_id' => $targetProject->getId(),
                    'target_parent_id' => $targetParentId,
                    'pre_file_id' => $preFileId,
                    'keep_both_file_ids' => $keepBothFileIds,
                ]);

                $publisher = new FileBatchCopyPublisher($event);
                $this->producer->produce($publisher);

                // Return asynchronous response
                return FileBatchOperationResponseDTO::createAsyncProcessing($batchKey)->toArray();
            }

            // 6. Single file sync copy
            $newFileEntity = $this->taskFileDomainService->copyProjectFile(
                $dataIsolation,
                $fileEntity,
                $sourceProject,
                $targetProject,
                $targetParentId,
                $keepBothFileIds
            );

            // 7. Handle file sorting in target project
            $this->taskFileDomainService->handleFileSortOnCopy(
                $newFileEntity,
                $targetProject,
                $targetParentId,
                $preFileId
            );

            // 8. Re-get file entity with updated data
            $newFileEntity = $this->taskFileDomainService->getById($newFileEntity->getFileId());

            $result = TaskFileItemDTO::fromEntity($newFileEntity)->toArray();
            return FileBatchOperationResponseDTO::createSyncSuccess($result)->toArray();
        } catch (BusinessException $e) {
            $this->logger->warning('Business logic error in copy file', [
                'file_id' => $fileId,
                'source_project_id' => isset($sourceProject) ? $sourceProject->getId() : null,
                'target_project_id' => isset($targetProject) ? $targetProject->getId() : null,
                'target_parent_id' => $targetParentId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('System error in copy file', [
                'file_id' => $fileId,
                'source_project_id' => isset($sourceProject) ? $sourceProject->getId() : null,
                'target_project_id' => isset($targetProject) ? $targetProject->getId() : null,
                'target_parent_id' => $targetParentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_COPY_FAILED, trans('file.file_copy_failed'));
        }
    }

    /**
     * Get file URLs for multiple files.
     *
     * @param RequestContext $requestContext Request context
     * @param string $projectId Project ID
     * @param array $fileIds Array of file IDs
     * @param string $downloadMode Download mode (download, preview)
     * @param array $options Additional options
     * @return array File URLs
     */
    public function getFileUrls(RequestContext $requestContext, string $projectId, array $fileIds, string $downloadMode, array $options = [], array $fileVersions = []): array
    {
        try {
            $userAuthorization = $requestContext->getUserAuthorization();
            $dataIsolation = $this->createDataIsolation($userAuthorization);

            $projectEntity = $this->getAccessibleProject((int) $projectId, $dataIsolation->getCurrentUserId(), $dataIsolation->getCurrentOrganizationCode());

            return $this->taskFileDomainService->getFileUrls(
                $projectEntity->getUserOrganizationCode(),
                $projectEntity->getId(),
                $fileIds,
                $downloadMode,
                $options,
                $fileVersions,
                true
            );
        } catch (BusinessException $e) {
            $this->logger->warning(sprintf(
                'Business logic error in get file URLs: %s, File IDs: %s, Download Mode: %s, Error Code: %d',
                $e->getMessage(),
                implode(',', $fileIds),
                $downloadMode,
                $e->getCode()
            ));
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'System error in get file URLs: %s, File IDs: %s, Download Mode: %s',
                $e->getMessage(),
                implode(',', $fileIds),
                $downloadMode
            ));
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_NOT_FOUND, trans('file.get_urls_failed'));
        }
    }

    /**
     * Get file URLs by access token.
     *
     * @param array $fileIds Array of file IDs
     * @param string $accessToken Access token for verification
     * @param string $downloadMode Download mode (download, preview)
     * @param array $fileVersions File version mapping [new parameter]
     * @return array File URLs
     */
    public function getFileUrlsByAccessToken(array $fileIds, string $accessToken, string $downloadMode, array $fileVersions = []): array
    {
        try {
            // Get data from cache
            if (! AccessTokenUtil::validate($accessToken)) {
                ExceptionBuilder::throw(GenericErrorCode::AccessDenied, 'task_file.access_denied');
            }

            // Get content from token
            $shareId = AccessTokenUtil::getShareId($accessToken);
            $shareEntity = $this->resourceShareDomainService->getValidShareById($shareId);
            if (! $shareEntity) {
                ExceptionBuilder::throw(ShareErrorCode::RESOURCE_NOT_FOUND, 'share.resource_not_found');
            }

            $projectId = 0;
            switch ($shareEntity->getResourceType()) {
                case ResourceType::Topic->value:
                    $topicEntity = $this->topicDomainService->getTopicWithDeleted((int) $shareEntity->getResourceId());
                    if (empty($topicEntity)) {
                        ExceptionBuilder::throw(BeAgentErrorCode::TOPIC_NOT_FOUND, 'topic.topic_not_found');
                    }
                    $projectId = $topicEntity->getProjectId();
                    break;
                case ResourceType::Project->value:
                    $projectId = (int) $shareEntity->getResourceId();
                    break;
                default:
                    ExceptionBuilder::throw(ShareErrorCode::RESOURCE_TYPE_NOT_SUPPORTED, 'share.resource_type_not_supported');
            }

            return $this->taskFileDomainService->getFileUrlsByProjectId($fileIds, $projectId, $downloadMode, $fileVersions);
        } catch (BusinessException $e) {
            $this->logger->warning(sprintf(
                'Business logic error in get file URLs by token: %s, File IDs: %s, Download Mode: %s, Error Code: %d',
                $e->getMessage(),
                implode(',', $fileIds),
                $downloadMode,
                $e->getCode()
            ));
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'System error in get file URLs by token: %s, File IDs: %s, Download Mode: %s',
                $e->getMessage(),
                implode(',', $fileIds),
                $downloadMode
            ));
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_NOT_FOUND, trans('file.get_urls_by_token_failed'));
        }
    }

    /**
     * Batch move files.
     *
     * @param RequestContext $requestContext Request context
     * @param BatchMoveFileRequestDTO $requestDTO Request DTO
     * @return array Batch move result
     */
    public function batchMoveFile(RequestContext $requestContext, BatchMoveFileRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        try {
            // 1. Get source project and verify permission
            $sourceProject = $this->getAccessibleProjectWithEditor(
                (int) $requestDTO->getProjectId(),
                $userAuthorization->getId(),
                $userAuthorization->getOrganizationCode()
            );

            // 2. Get target project (if not provided, use source project)
            $targetProject = ! empty($requestDTO->getTargetProjectId())
                ? $this->getAccessibleProjectWithEditor(
                    (int) $requestDTO->getTargetProjectId(),
                    $userAuthorization->getId(),
                    $userAuthorization->getOrganizationCode()
                )
                : $sourceProject;

            // Generate batch key for tracking
            $fileIds = $requestDTO->getFileIds();
            sort($fileIds); // Ensure consistent hash for same file IDs
            $fileIdsHash = md5(implode(',', $fileIds));
            $batchKey = $this->batchOperationStatusManager->generateBatchKey(
                FileBatchOperationStatusManager::OPERATION_MOVE,
                $dataIsolation->getCurrentUserId(),
                $fileIdsHash
            );

            // Expand directory file IDs to include all nested files
            $expandedFileIds = $this->expandDirectoryFileIds(
                $dataIsolation,
                $requestDTO->getFileIds(),
                $sourceProject->getId()
            );

            $this->logger->info('Expanded directory file IDs for batch move', [
                'batch_key' => $batchKey,
                'original_file_ids' => $requestDTO->getFileIds(),
                'expanded_file_ids' => $expandedFileIds,
                'original_count' => count($requestDTO->getFileIds()),
                'expanded_count' => count($expandedFileIds),
            ]);

            // Initialize task status with expanded file count
            $this->batchOperationStatusManager->initializeTask(
                $batchKey,
                FileBatchOperationStatusManager::OPERATION_MOVE,
                $dataIsolation->getCurrentUserId(),
                count($expandedFileIds)
            );

            // Print request data
            $this->logger->info(sprintf('Batch move file request data, batchKey: %s', $batchKey), [
                'file_ids' => $requestDTO->getFileIds(),
                'expanded_file_ids' => $expandedFileIds,
                'source_project_id' => $sourceProject->getId(),
                'target_project_id' => $targetProject->getId(),
                'target_parent_id' => $requestDTO->getTargetParentId(),
                'pre_file_id' => $requestDTO->getPreFileId(),
                'keep_both_file_ids' => $requestDTO->getKeepBothFileIds(),
            ]);

            // Create and publish batch move event
            $preFileId = ! empty($requestDTO->getPreFileId()) ? (int) $requestDTO->getPreFileId() : null;
            if (empty($requestDTO->getTargetParentId())) {
                $targetParentId = $this->taskFileDomainService->findOrCreateProjectRootDirectory(
                    projectId: $targetProject->getId(),
                    workDir: $targetProject->getWorkDir(),
                    userId: $dataIsolation->getCurrentUserId(),
                    organizationCode: $dataIsolation->getCurrentOrganizationCode(),
                    projectOrganizationCode: $targetProject->getUserOrganizationCode()
                );
            } else {
                $targetParentId = (int) $requestDTO->getTargetParentId();
            }
            $event = FileBatchMoveEvent::fromDTO(
                $batchKey,
                $dataIsolation->getCurrentUserId(),
                $dataIsolation->getCurrentOrganizationCode(),
                $expandedFileIds,
                $targetProject->getId(),
                $sourceProject->getId(),
                $preFileId,
                $targetParentId,
                $requestDTO->getKeepBothFileIds()
            );
            $publisher = new FileBatchMovePublisher($event);
            $this->producer->produce($publisher);
            $this->eventDispatcher->dispatch($event);

            // Return asynchronous response
            return FileBatchOperationResponseDTO::createAsyncProcessing($batchKey)->toArray();
        } catch (BusinessException $e) {
            $this->logger->warning('Business logic error in batch move file', [
                'file_ids' => $requestDTO->getFileIds(),
                'source_project_id' => isset($sourceProject) ? $sourceProject->getId() : null,
                'target_project_id' => isset($targetProject) ? $targetProject->getId() : null,
                'target_parent_id' => $requestDTO->getTargetParentId(),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('System error in batch move file', [
                'file_ids' => $requestDTO->getFileIds(),
                'source_project_id' => isset($sourceProject) ? $sourceProject->getId() : null,
                'target_project_id' => isset($targetProject) ? $targetProject->getId() : null,
                'target_parent_id' => $requestDTO->getTargetParentId(),
                'error' => $e->getMessage(),
            ]);
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_MOVE_FAILED, trans('file.batch_move_failed'));
        }
    }

    /**
     * Batch copy files to target directory (supports both same-project and cross-project copy).
     *
     * @param RequestContext $requestContext Request context
     * @param BatchCopyFileRequestDTO $requestDTO Request DTO
     * @return array Batch copy result
     */
    public function batchCopyFile(RequestContext $requestContext, BatchCopyFileRequestDTO $requestDTO): array
    {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        try {
            // 1. Get source project and verify permission
            $sourceProject = $this->getAccessibleProjectWithEditor(
                (int) $requestDTO->getProjectId(),
                $userAuthorization->getId(),
                $userAuthorization->getOrganizationCode()
            );

            // 2. Get target project (if not provided, use source project)
            $targetProject = ! empty($requestDTO->getTargetProjectId())
                ? $this->getAccessibleProjectWithEditor(
                    (int) $requestDTO->getTargetProjectId(),
                    $userAuthorization->getId(),
                    $userAuthorization->getOrganizationCode()
                )
                : $sourceProject;

            // Generate batch key for tracking
            $fileIds = $requestDTO->getFileIds();
            sort($fileIds); // Ensure consistent hash for same file IDs
            $fileIdsHash = md5(implode(',', $fileIds));
            $batchKey = $this->batchOperationStatusManager->generateBatchKey(
                FileBatchOperationStatusManager::OPERATION_COPY,
                $dataIsolation->getCurrentUserId(),
                $fileIdsHash
            );

            // Expand directory file IDs to include all nested files
            $expandedFileIds = $this->expandDirectoryFileIds(
                $dataIsolation,
                $requestDTO->getFileIds(),
                $sourceProject->getId()
            );

            $this->logger->info('Expanded directory file IDs for batch copy', [
                'batch_key' => $batchKey,
                'original_file_ids' => $requestDTO->getFileIds(),
                'expanded_file_ids' => $expandedFileIds,
                'original_count' => count($requestDTO->getFileIds()),
                'expanded_count' => count($expandedFileIds),
            ]);

            // Initialize task status with expanded file count
            $this->batchOperationStatusManager->initializeTask(
                $batchKey,
                FileBatchOperationStatusManager::OPERATION_COPY,
                $dataIsolation->getCurrentUserId(),
                count($expandedFileIds)
            );

            // Print request data
            $this->logger->info(sprintf('Batch copy file request data, batchKey: %s', $batchKey), [
                'file_ids' => $requestDTO->getFileIds(),
                'expanded_file_ids' => $expandedFileIds,
                'source_project_id' => $sourceProject->getId(),
                'target_project_id' => $targetProject->getId(),
                'target_parent_id' => $requestDTO->getTargetParentId(),
                'pre_file_id' => $requestDTO->getPreFileId(),
                'keep_both_file_ids' => $requestDTO->getKeepBothFileIds(),
            ]);

            // Create and publish batch copy event
            $preFileId = ! empty($requestDTO->getPreFileId()) ? (int) $requestDTO->getPreFileId() : null;
            if (empty($requestDTO->getTargetParentId())) {
                $targetParentId = $this->taskFileDomainService->findOrCreateProjectRootDirectory(
                    projectId: $targetProject->getId(),
                    workDir: $targetProject->getWorkDir(),
                    userId: $dataIsolation->getCurrentUserId(),
                    organizationCode: $dataIsolation->getCurrentOrganizationCode(),
                    projectOrganizationCode: $targetProject->getUserOrganizationCode()
                );
            } else {
                $targetParentId = (int) $requestDTO->getTargetParentId();
            }
            $event = FileBatchCopyEvent::fromDTO(
                $batchKey,
                $dataIsolation->getCurrentUserId(),
                $dataIsolation->getCurrentOrganizationCode(),
                $expandedFileIds,
                $targetProject->getId(),
                $sourceProject->getId(),
                $preFileId,
                $targetParentId,
                $requestDTO->getKeepBothFileIds()
            );
            $publisher = new FileBatchCopyPublisher($event);
            $this->producer->produce($publisher);

            // Return asynchronous response
            return FileBatchOperationResponseDTO::createAsyncProcessing($batchKey)->toArray();
        } catch (BusinessException $e) {
            $this->logger->warning('Business logic error in batch copy file', [
                'file_ids' => $requestDTO->getFileIds(),
                'source_project_id' => isset($sourceProject) ? $sourceProject->getId() : null,
                'target_project_id' => isset($targetProject) ? $targetProject->getId() : null,
                'target_parent_id' => $requestDTO->getTargetParentId(),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('System error in batch copy file', [
                'file_ids' => $requestDTO->getFileIds(),
                'source_project_id' => isset($sourceProject) ? $sourceProject->getId() : null,
                'target_project_id' => isset($targetProject) ? $targetProject->getId() : null,
                'target_parent_id' => $requestDTO->getTargetParentId(),
                'error' => $e->getMessage(),
            ]);
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_COPY_FAILED, trans('file.batch_copy_failed'));
        }
    }

    /**
     * Check batch operation status.
     *
     * @param RequestContext $requestContext Request context
     * @param CheckBatchOperationStatusRequestDTO $requestDTO Request DTO
     * @return FileBatchOperationStatusResponseDTO Response DTO
     */
    public function checkBatchOperationStatus(
        RequestContext $requestContext,
        CheckBatchOperationStatusRequestDTO $requestDTO
    ): FileBatchOperationStatusResponseDTO {
        try {
            $batchKey = $requestDTO->getBatchKey();
            $userAuthorization = $requestContext->getUserAuthorization();
            $dataIsolation = $this->createDataIsolation($userAuthorization);

            // Verify user permission for this batch operation
            if (! $this->batchOperationStatusManager->verifyUserPermission($batchKey, $dataIsolation->getCurrentUserId())) {
                $this->logger->warning('User permission denied for batch operation status check', [
                    'batch_key' => $batchKey,
                    'user_id' => $dataIsolation->getCurrentUserId(),
                ]);
                return FileBatchOperationStatusResponseDTO::createNotFound();
            }

            // Get task status from Redis
            $taskStatus = $this->batchOperationStatusManager->getTaskStatus($batchKey);

            if (! $taskStatus) {
                $this->logger->info('Batch operation not found', [
                    'batch_key' => $batchKey,
                    'user_id' => $dataIsolation->getCurrentUserId(),
                ]);
                return FileBatchOperationStatusResponseDTO::createNotFound();
            }

            // Log the status check
            $this->logger->debug('Batch operation status retrieved', [
                'batch_key' => $batchKey,
                'status' => $taskStatus['status'] ?? 'unknown',
                'operation' => $taskStatus['operation'] ?? 'unknown',
                'user_id' => $dataIsolation->getCurrentUserId(),
            ]);

            // Create response DTO from task status
            return FileBatchOperationStatusResponseDTO::fromTaskStatus($taskStatus);
        } catch (BusinessException $e) {
            $this->logger->warning('Business logic error in checking batch operation status', [
                'batch_key' => $requestDTO->getBatchKey(),
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('System error in checking batch operation status', [
                'batch_key' => $requestDTO->getBatchKey(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            ExceptionBuilder::throw(BeAgentErrorCode::FILE_NOT_FOUND, trans('file.check_batch_status_failed'));
        }
    }

    /**
     * Replace file with new file.
     *
     * @param RequestContext $requestContext Request context
     * @param int $fileId Target file ID to replace
     * @param ReplaceFileRequestDTO $requestDTO Request DTO
     * @return array Replaced file information
     */
    public function replaceFile(
        RequestContext $requestContext,
        int $fileId,
        ReplaceFileRequestDTO $requestDTO
    ): array {
        $userAuthorization = $requestContext->getUserAuthorization();
        $dataIsolation = $this->createDataIsolation($userAuthorization);

        try {
            // 1. Permission validation and file existence check
            $fileEntity = $this->taskFileDomainService->getById($fileId);
            if (empty($fileEntity)) {
                ExceptionBuilder::throw(
                    BeAgentErrorCode::FILE_NOT_FOUND,
                    trans('file.file_not_found')
                );
            }

            // Check if it's a directory (boundary 1: directory replacement not allowed)
            if ($fileEntity->getIsDirectory()) {
                ExceptionBuilder::throw(
                    BeAgentErrorCode::FILE_OPERATION_NOT_ALLOWED,
                    trans('file.cannot_replace_directory')
                );
            }

            // Check project permissions
            $projectEntity = $this->getAccessibleProject(
                $fileEntity->getProjectId(),
                $userAuthorization->getId(),
                $userAuthorization->getOrganizationCode()
            );

            // 2. Check file editing status (boundary 2: file is being edited)
            // TODO: Implement editing status check logic
            // $editingUsers = $this->getFileEditingUsers($fileId);
            // if (!empty($editingUsers) && !$requestDTO->getForceReplace()) {
            //     ExceptionBuilder::throw(...);
            // }

            // 3. Verify new file exists in cloud storage (boundary 3: source file does not exist)
            $newFileKey = $requestDTO->getFileKey();
            $organizationCode = $projectEntity->getUserOrganizationCode();
            $newFileInfo = $this->taskFileDomainService->getFileInfoFromCloudStorage(
                $newFileKey,
                $organizationCode
            );

            if (empty($newFileInfo)) {
                ExceptionBuilder::throw(
                    BeAgentErrorCode::FILE_NOT_FOUND,
                    trans('file.source_file_not_found_in_storage')
                );
            }

            // 4. Build new file name and target file_key
            // Scenario 1: New file name provided -> use user-specified file name
            // Scenario 2: File name not provided -> extract file name from new file's file_key
            if (! empty($requestDTO->getFileName())) {
                $newFileName = $requestDTO->getFileName();
            } else {
                // Extract file name from new file path
                $newFileName = basename($newFileKey);
            }

            // Build target file path: original file directory + new file name
            $targetFileKey = dirname($fileEntity->getFileKey()) . '/' . $newFileName;

            $newFileExtension = pathinfo($newFileName, PATHINFO_EXTENSION);
            $oldFileExtension = $fileEntity->getFileExtension();

            // Detect cross-type replacement (boundary 4: file type change)
            $isCrossTypeReplace = ($oldFileExtension !== $newFileExtension);

            // 5. File name conflict check (boundary 5: another file already exists at target location)
            if ($targetFileKey !== $fileEntity->getFileKey()) {
                $existingFile = $this->taskFileDomainService->getByFileKey($targetFileKey);
                if (! empty($existingFile)) {
                    ExceptionBuilder::throw(
                        BeAgentErrorCode::FILE_EXIST,
                        trans('file.target_file_already_exists')
                    );
                }
            }

            // 6. Working directory security check (boundary 6: prevent path traversal)
            $fullPrefix = $this->taskFileDomainService->getFullPrefix($organizationCode);
            $fullWorkdir = WorkDirectoryUtil::getFullWorkdir($fullPrefix, $projectEntity->getWorkDir());

            if (! WorkDirectoryUtil::checkEffectiveFileKey($fullWorkdir, $targetFileKey)) {
                ExceptionBuilder::throw(
                    BeAgentErrorCode::FILE_ILLEGAL_KEY,
                    trans('file.illegal_file_key')
                );
            }

            if (! WorkDirectoryUtil::checkEffectiveFileKey($fullWorkdir, $newFileKey)) {
                ExceptionBuilder::throw(
                    BeAgentErrorCode::FILE_ILLEGAL_KEY,
                    trans('file.source_file_key_illegal')
                );
            }

            Db::beginTransaction();
            try {
                $prefix = WorkDirectoryUtil::getPrefix($projectEntity->getWorkDir());
                $oldFileKey = $fileEntity->getFileKey();

                // 7. Create version snapshot (before replacement)
                $versionEntity = $this->taskFileVersionDomainService->createFileVersion(
                    $projectEntity->getUserOrganizationCode(),
                    $fileEntity,
                    $isCrossTypeReplace ? 2 : 1  // Cross-type replacement uses special marker
                );

                if (empty($versionEntity)) {
                    $this->logger->warning('Failed to create version snapshot before replace', [
                        'file_id' => $fileId,
                    ]);
                }

                if ($oldFileKey !== $targetFileKey) {
                    $this->cloudFileRepository->deleteObjectByCredential(
                        $prefix,
                        $organizationCode,
                        $oldFileKey,
                        StorageBucketType::SandBox
                    );

                    $this->logger->info('Old file deleted after version backup', [
                        'file_id' => $fileId,
                        'old_file_key' => $oldFileKey,
                        'version_id' => $versionEntity?->getId(),
                    ]);

                    // 8.2 Move new file to target location (if needed)
                    $this->cloudFileRepository->renameObjectByCredential(
                        $prefix,
                        $organizationCode,
                        $newFileKey,
                        $targetFileKey,
                        StorageBucketType::SandBox
                    );

                    $this->logger->info('New file moved to target location', [
                        'file_id' => $fileId,
                        'source_key' => $newFileKey,
                        'target_key' => $targetFileKey,
                    ]);
                }

                // 9. Update database record
                $fileEntity->setFileKey($targetFileKey);
                $fileEntity->setFileName($newFileName);
                $fileEntity->setFileExtension($newFileExtension);
                $fileEntity->setFileSize($newFileInfo['size']);
                $fileEntity->setUpdatedAt(date('Y-m-d H:i:s'));
                $newFileEntity = $this->taskFileDomainService->updateById($fileEntity);

                Db::commit();

                // 10. Publish event
                $fileReplacedEvent = new FileReplacedEvent(
                    $newFileEntity,
                    $versionEntity,
                    $userAuthorization,
                    $isCrossTypeReplace
                );
                $this->eventDispatcher->dispatch($fileReplacedEvent);

                // 11. Return result
                return TaskFileItemDTO::fromEntity($newFileEntity, $projectEntity->getWorkDir())->toArray();
            } catch (Throwable $e) {
                Db::rollBack();

                $this->logger->error('Failed to replace file, transaction rolled back', [
                    'file_id' => $fileId,
                    'source_key' => $newFileKey,
                    'target_key' => $targetFileKey,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        } catch (BusinessException $e) {
            $this->logger->warning(sprintf(
                'Business logic error in replace file: %s, File ID: %s, Error Code: %d',
                $e->getMessage(),
                $fileId,
                $e->getCode()
            ));
            throw $e;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'System error in replace file: %s, File ID: %s',
                $e->getMessage(),
                $fileId
            ));
            ExceptionBuilder::throw(
                BeAgentErrorCode::FILE_REPLACE_FAILED,
                trans('file.file_replace_failed')
            );
        }
    }

    /**
     * Expand directory file IDs to include all nested files.
     *
     * This method processes a list of file IDs and expands any directories
     * to include all their nested files. This ensures that when moving or
     * operating on directories, all contained files are included.
     *
     * @param DataIsolation $dataIsolation Data isolation context
     * @param array $fileIds Original file IDs (may contain directories)
     * @param int $projectId Project ID
     * @return array Expanded file IDs (includes all nested files from directories)
     */
    private function expandDirectoryFileIds(DataIsolation $dataIsolation, array $fileIds, int $projectId): array
    {
        $allFileIds = [];

        // Get all file entities
        $fileEntities = $this->taskFileDomainService->getProjectFilesByIds($projectId, $fileIds);

        foreach ($fileEntities as $fileEntity) {
            // Always include the file/directory itself
            $allFileIds[] = $fileEntity->getFileId();

            // If it's a directory, expand to get all nested files
            if ($fileEntity->getIsDirectory()) {
                $nestedFileIds = $this->taskFileDomainService->getDirectoryFileIds(
                    $dataIsolation,
                    $fileEntity
                );

                // Merge nested file IDs
                if (! empty($nestedFileIds)) {
                    $allFileIds = array_merge($allFileIds, $nestedFileIds);
                }
            }
        }

        // Remove duplicates and reindex
        return array_values(array_unique($allFileIds));
    }
}
