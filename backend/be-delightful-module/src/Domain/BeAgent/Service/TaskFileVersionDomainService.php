<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Service;

use App\Domain\File\Repository\Persistence\Facade\CloudFileRepositoryInterface;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileEntity;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileVersionEntity;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskFileRepositoryInterface;
use Delightful\BeDelightful\Domain\BeAgent\Repository\Facade\TaskFileVersionRepositoryInterface;
use Hyperf\Logger\LoggerFactory;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

class TaskFileVersionDomainService
{
    private readonly LoggerInterface $logger;

    public function __construct(
        protected TaskFileVersionRepositoryInterface $taskFileVersionRepository,
        protected TaskFileRepositoryInterface $taskFileRepository,
        protected CloudFileRepositoryInterface $cloudFileRepository,
        LoggerFactory $loggerFactory
    ) {
        $this->logger = $loggerFactory->get(get_class($this));
    }

    /**
     * Create file version.
     */
    public function createFileVersion(string $projectOrganizationCode, TaskFileEntity $fileEntity, int $editType = 1): ?TaskFileVersionEntity
    {
        // Only create version for non-directory files
        if ($fileEntity->getIsDirectory()) {
            $this->logger->info('Skipping version creation for directory file', [
                'file_id' => $fileEntity->getFileId(),
                'file_name' => $fileEntity->getFileName(),
            ]);
            return null;
        }

        // 1. Get next version number
        $nextVersion = $this->getNextVersionNumber($fileEntity->getFileId());

        // 2. Generate version file path
        $versionFileKey = $this->generateVersionFileKey($fileEntity->getFileKey(), $nextVersion);

        // 3. Copy OSS file to version path
        $this->logger->info('Copying file to version path', [
            'organization_code' => $fileEntity->getOrganizationCode(),
            'source_key' => $fileEntity->getFileKey(),
            'destination_key' => $versionFileKey,
        ]);

        $this->copyFile(
            $projectOrganizationCode,
            $fileEntity->getFileKey(),
            $versionFileKey
        );

        $this->logger->info('File copied to version path successfully', [
            'organization_code' => $fileEntity->getOrganizationCode(),
            'source_key' => $fileEntity->getFileKey(),
            'destination_key' => $versionFileKey,
        ]);

        // 4. Create version record
        $versionEntity = new TaskFileVersionEntity();
        $versionEntity->setId(IdGenerator::getSnowId());
        $versionEntity->setFileId($fileEntity->getFileId());
        $versionEntity->setOrganizationCode($fileEntity->getOrganizationCode());
        $versionEntity->setFileKey($versionFileKey);
        $versionEntity->setVersion($nextVersion);
        $versionEntity->setEditType($editType);

        $savedEntity = $this->taskFileVersionRepository->insert($versionEntity);

        // 5. Clean up old versions
        $maxVersions = (int) config('be-delightful.file_version.max_versions', 10);
        $this->cleanupOldVersions($fileEntity->getFileId(), $maxVersions);

        $this->logger->info('File version created successfully', [
            'file_id' => $fileEntity->getFileId(),
            'version' => $nextVersion,
            'version_file_key' => $versionFileKey,
            'edit_type' => $editType,
        ]);

        return $savedEntity;
    }

    /**
     * Get file history version list.
     */
    public function getFileVersions(int $fileId): array
    {
        return $this->taskFileVersionRepository->getByFileId($fileId);
    }

    /**
     * Get file history version list with pagination.
     *
     * @param int $fileId File ID
     * @param int $page Page number (starting from 1)
     * @param int $pageSize Items per page
     * @return array Array containing list (TaskFileVersionEntity array) and total (total count)
     */
    public function getFileVersionsWithPage(int $fileId, int $page, int $pageSize): array
    {
        $this->logger->info('Getting file versions with pagination', [
            'file_id' => $fileId,
            'page' => $page,
            'page_size' => $pageSize,
        ]);

        // Call repository layer pagination query method
        $result = $this->taskFileVersionRepository->getByFileIdWithPage($fileId, $page, $pageSize);

        $this->logger->info('File versions retrieved successfully', [
            'file_id' => $fileId,
            'total' => $result['total'],
            'current_page_count' => count($result['list']),
        ]);

        return $result;
    }

    /**
     * Batch cleanup versions for multiple files (for scheduled tasks and similar scenarios).
     */
    public function batchCleanupFileVersions(array $fileIds, int $maxVersions): array
    {
        $stats = [
            'total_files' => count($fileIds),
            'processed_files' => 0,
            'total_deleted' => 0,
            'errors' => [],
        ];

        foreach ($fileIds as $fileId) {
            try {
                $deletedCount = $this->cleanupOldVersions($fileId, $maxVersions);
                $stats['total_deleted'] += $deletedCount;
                ++$stats['processed_files'];
            } catch (Throwable $e) {
                $stats['errors'][] = [
                    'file_id' => $fileId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $stats;
    }

    /**
     * Rollback file to specified version.
     */
    public function rollbackFileToVersion(string $projectOrganizationCode, TaskFileEntity $fileEntity, int $targetVersion): ?TaskFileVersionEntity
    {
        $fileId = $fileEntity->getFileId();

        $this->logger->info('Starting file rollback to version', [
            'file_id' => $fileId,
            'target_version' => $targetVersion,
            'organization_code' => $projectOrganizationCode,
        ]);

        // 1. Validate target version exists
        $targetVersionEntity = $this->taskFileVersionRepository->getByFileIdAndVersion($fileId, $targetVersion);
        if (! $targetVersionEntity) {
            $this->logger->error('Target version not found', [
                'file_id' => $fileId,
                'target_version' => $targetVersion,
            ]);
            return null;
        }

        $this->logger->info('Target version found', [
            'file_id' => $fileId,
            'target_version' => $targetVersion,
            'version_file_key' => $targetVersionEntity->getFileKey(),
        ]);

        // 2. Copy version file to current file location
        $currentFileKey = $fileEntity->getFileKey();
        $versionFileKey = $targetVersionEntity->getFileKey();

        $this->logger->info('Copying version file to current file location', [
            'source_key' => $versionFileKey,
            'destination_key' => $currentFileKey,
        ]);

        $this->copyFile(
            $projectOrganizationCode,
            $versionFileKey,
            $currentFileKey
        );

        $this->logger->info('File copied from version to workspace successfully', [
            'source_key' => $versionFileKey,
            'destination_key' => $currentFileKey,
        ]);

        // 3. Reuse createFileVersion method to create new version record for rolled back file
        $this->logger->info('Creating new version record after rollback using createFileVersion', [
            'file_id' => $fileId,
        ]);

        $newVersionEntity = $this->createFileVersion($projectOrganizationCode, $fileEntity);

        if (! $newVersionEntity) {
            $this->logger->error('Failed to create version record after rollback', [
                'file_id' => $fileId,
                'target_version' => $targetVersion,
            ]);
            return null;
        }

        $this->logger->info('File rollback completed successfully', [
            'file_id' => $fileId,
            'target_version' => $targetVersion,
            'new_version' => $newVersionEntity->getVersion(),
            'new_version_id' => $newVersionEntity->getId(),
        ]);

        return $newVersionEntity;
    }

    /**
     * Get file's latest version number.
     */
    public function getLatestVersionNumber(int $fileId): int
    {
        return $this->taskFileVersionRepository->getLatestVersionNumber($fileId);
    }

    /**
     * Get next version number.
     */
    private function getNextVersionNumber(int $fileId): int
    {
        $latestVersion = $this->taskFileVersionRepository->getLatestVersionNumber($fileId);
        return $latestVersion + 1;
    }

    /**
     * Generate version file key.
     */
    private function generateVersionFileKey(string $originalFileKey, int $version): string
    {
        // Validate original file path contains /workspace/
        if (! str_contains($originalFileKey, '/workspace/')) {
            throw new InvalidArgumentException('Original file key must contain /workspace/ path');
        }

        // Only replace first occurrence of /workspace/ with /version/
        $pos = strpos($originalFileKey, '/workspace/');
        if ($pos !== false) {
            $versionBasePath = substr_replace($originalFileKey, '/version/', $pos, strlen('/workspace/'));
        } else {
            // Theoretically should never reach here, since already validated above
            throw new InvalidArgumentException('Original file key must contain /workspace/ path');
        }

        // Append version number after filename
        return $versionBasePath . '/' . $version;
    }

    /**
     * Clean up old versions, keep specified number of latest versions.
     */
    private function cleanupOldVersions(int $fileId, int $maxVersions): int
    {
        try {
            // 1. Get current version count
            $currentCount = $this->taskFileVersionRepository->countByFileId($fileId);

            if ($currentCount <= $maxVersions) {
                return 0; // No need to cleanup
            }

            $this->logger->info('Starting version cleanup', [
                'file_id' => $fileId,
                'current_count' => $currentCount,
                'max_versions' => $maxVersions,
                'to_delete' => $currentCount - $maxVersions,
            ]);

            // 2. Get list of version entities to delete
            $versionsToDelete = $this->taskFileVersionRepository->getVersionsToCleanup($fileId, $maxVersions);

            if (empty($versionsToDelete)) {
                return 0;
            }

            // 3. Delete OSS files first
            $ossDeletedCount = 0;
            $ossFailedCount = 0;

            foreach ($versionsToDelete as $versionEntity) {
                $prefix = '/';

                $this->cloudFileRepository->deleteObjectByCredential(
                    $prefix,
                    $versionEntity->getOrganizationCode(),
                    $versionEntity->getFileKey(),
                    StorageBucketType::SandBox
                );

                ++$ossDeletedCount;

                $this->logger->debug('Version file deleted from OSS', [
                    'version_id' => $versionEntity->getId(),
                    'file_key' => $versionEntity->getFileKey(),
                ]);
            }

            // 4. Batch delete database records (regardless of OSS deletion success)
            $dbDeletedCount = $this->taskFileVersionRepository->deleteOldVersionsByFileId($fileId, $maxVersions);

            $this->logger->info('Version cleanup completed', [
                'file_id' => $fileId,
                'target_delete_count' => count($versionsToDelete),
                'db_deleted_count' => $dbDeletedCount,
                'oss_deleted_count' => $ossDeletedCount,
                'oss_failed_count' => $ossFailedCount,
            ]);

            return $dbDeletedCount;
        } catch (Throwable $e) {
            $this->logger->error('Version cleanup failed', [
                'file_id' => $fileId,
                'max_versions' => $maxVersions,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function copyFile(string $organizationCode, string $sourceKey, string $destinationKey): void
    {
        // Extract prefix from source file path (for determining operation permissions)
        $prefix = '/';

        // Use existing copy file functionality
        $this->cloudFileRepository->copyObjectByCredential(
            $prefix,
            $organizationCode,
            $sourceKey,
            $destinationKey,
            StorageBucketType::SandBox,
            [
                'metadata_directive' => 'COPY', // Copy original file metadata
            ]
        );

        $this->logger->info('File copied successfully', [
            'organization_code' => $organizationCode,
            'source_key' => $sourceKey,
            'destination_key' => $destinationKey,
            'prefix' => $prefix,
        ]);
    }
}
