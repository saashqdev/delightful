<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\File\Service;

use App\Domain\File\DTO\CloudFileInfoDTO;
use App\Domain\File\Repository\Persistence\CloudFileRepository;
use App\Domain\File\Repository\Persistence\Facade\CloudFileRepositoryInterface;
use App\Infrastructure\Core\ValueObject\StorageBucketType;
use BeDelightful\CloudFile\Kernel\Struct\ChunkUploadFile;
use BeDelightful\CloudFile\Kernel\Struct\FileLink;
use BeDelightful\CloudFile\Kernel\Struct\FilePreSignedUrl;
use BeDelightful\CloudFile\Kernel\Struct\UploadFile;

readonly class FileDomainService
{
    public function __construct(
        private CloudFileRepositoryInterface $cloudFileRepository
    ) {
    }

    public function getDefaultIcons(): array
    {
        $paths = $this->cloudFileRepository->getDefaultIconPaths();
        $links = $this->cloudFileRepository->getLinks(CloudFileRepository::DEFAULT_ICON_ORGANIZATION_CODE, array_values($paths), StorageBucketType::Public);
        $list = [];
        foreach ($links as $link) {
            // Get file name without extension
            $fileName = pathinfo($link->getPath(), PATHINFO_FILENAME);
            $list[$fileName] = $link->getUrl();
        }
        return $list;
    }

    public function getDefaultIconPaths(): array
    {
        $paths = $this->cloudFileRepository->getDefaultIconPaths();
        $list = [];
        foreach ($paths as $path) {
            // Get file name without extension
            $fileName = pathinfo($path, PATHINFO_FILENAME);
            $list[$fileName] = $path;
        }
        return $list;
    }

    public function getLink(string $organizationCode, ?string $filePath, ?StorageBucketType $bucketType = null, array $downloadNames = [], array $options = []): ?FileLink
    {
        if (empty($filePath)) {
            return null;
        }
        if (is_url($filePath)) {
            // onlyneedpath
            $filePath = ltrim(parse_url($filePath, PHP_URL_PATH), '/');
        }
        return $this->cloudFileRepository->getLinks($organizationCode, [$filePath], $bucketType, $downloadNames, $options)[$filePath] ?? null;
    }

    public function uploadByCredential(string $organizationCode, UploadFile $uploadFile, StorageBucketType $storage = StorageBucketType::Private, bool $autoDir = true, ?string $contentType = null): void
    {
        $this->cloudFileRepository->uploadByCredential($organizationCode, $uploadFile, $storage, $autoDir, $contentType);
    }

    public function upload(string $organizationCode, UploadFile $uploadFile, StorageBucketType $storage = StorageBucketType::Private): void
    {
        $this->cloudFileRepository->upload($organizationCode, $uploadFile, $storage);
    }

    /**
     * Upload file using chunk upload.
     *
     * @param string $organizationCode Organization code
     * @param ChunkUploadFile $chunkUploadFile Chunk upload file object
     * @param StorageBucketType $storage Storage bucket type
     * @param bool $autoDir Whether to auto-generate directory
     */
    public function uploadByChunks(string $organizationCode, ChunkUploadFile $chunkUploadFile, StorageBucketType $storage = StorageBucketType::Private, bool $autoDir = true): void
    {
        $this->cloudFileRepository->uploadByChunks($organizationCode, $chunkUploadFile, $storage, $autoDir);
    }

    public function getSimpleUploadTemporaryCredential(string $organizationCode, StorageBucketType $storage = StorageBucketType::Private, ?string $contentType = null, bool $sts = false): array
    {
        return $this->cloudFileRepository->getSimpleUploadTemporaryCredential($organizationCode, $storage, contentType: $contentType, sts: $sts);
    }

    /**
     * @return array<string, FilePreSignedUrl>
     */
    public function getPreSignedUrls(string $organizationCode, array $fileNames, int $expires = 3600, StorageBucketType $bucketType = StorageBucketType::Private): array
    {
        return $this->cloudFileRepository->getPreSignedUrls($organizationCode, $fileNames, $expires, $bucketType);
    }

    /**
     * @return array<string,FileLink>
     */
    public function getLinks(string $organizationCode, array $filePaths, ?StorageBucketType $bucketType = null, array $downloadNames = [], array $options = []): array
    {
        return $this->cloudFileRepository->getLinks($organizationCode, $filePaths, $bucketType, $downloadNames, $options);
    }

    /**
     * batchquantitygetfilelink(fromautofrompathextractorganizationencodingandminutegroupprocess).
     * @param string[] $filePaths containorganizationencodingfilepatharray,format:orgCode/path/file.ext
     * @param null|StorageBucketType $bucketType storagebuckettype,defaultforPublic
     * @return array<string,FileLink> filepathtoFileLinkmapping
     */
    public function getBatchLinksByOrgPaths(array $filePaths, ?StorageBucketType $bucketType = null): array
    {
        // filteremptypathandalreadyalreadyisURLpath
        $validPaths = array_filter($filePaths, static fn ($path) => ! empty($path) && ! is_url($path));

        if (empty($validPaths)) {
            return [];
        }

        // byorganizationcodeminutegroupfilepath
        $pathsByOrg = [];
        foreach ($validPaths as $filePath) {
            $orgCode = explode('/', $filePath, 2)[0] ?? '';
            if (! empty($orgCode)) {
                $pathsByOrg[$orgCode][] = $filePath;
            }
        }

        // batchquantitygetfilelink
        $allLinks = [];
        foreach ($pathsByOrg as $orgCode => $paths) {
            $orgLinks = $this->getLinks($orgCode, $paths, $bucketType);
            $allLinks = array_merge($allLinks, $orgLinks);
        }

        return $allLinks;
    }

    /**
     * Download file using chunk download.
     *
     * @param string $organizationCode Organization code
     * @param string $filePath Remote file path
     * @param string $localPath Local save path
     * @param null|StorageBucketType $bucketType Storage bucket type
     * @param array $options Additional options (chunk_size, max_concurrency, etc.)
     */
    public function downloadByChunks(string $organizationCode, string $filePath, string $localPath, ?StorageBucketType $bucketType = null, array $options = []): void
    {
        $this->cloudFileRepository->downloadByChunks($organizationCode, $filePath, $localPath, $bucketType, $options);
    }

    public function getMetas(array $paths, string $organizationCode): array
    {
        return $this->cloudFileRepository->getMetas($paths, $organizationCode);
    }

    /**
     * start sts modetype.
     * gettemporaryvouchergivefrontclientuse.
     * @todo securityissue,dir nothavevalidation,nothaveorganizationisolation
     */
    public function getStsTemporaryCredential(
        string $organizationCode,
        StorageBucketType $bucketType = StorageBucketType::Private,
        string $dir = '',
        int $expires = 3600,
        bool $autoBucket = true,
    ): array {
        return $this->cloudFileRepository->getStsTemporaryCredential($organizationCode, $bucketType, $dir, $expires, $autoBucket);
    }

    public function exist(array $metas, string $key): bool
    {
        foreach ($metas as $meta) {
            if ($meta->getPath() === $key) {
                return true;
            }
        }
        return false;
    }

    /**
     * Delete file from storage.
     *
     * @param string $organizationCode Organization code
     * @param string $filePath File path to delete
     * @param StorageBucketType $bucketType Storage bucket type
     * @return bool True if deleted successfully, false otherwise
     */
    public function deleteFile(string $organizationCode, string $filePath, StorageBucketType $bucketType = StorageBucketType::Private): bool
    {
        return $this->cloudFileRepository->deleteFile($organizationCode, $filePath, $bucketType);
    }

    /**
     * Delete multiple objects by credential.
     *
     * @param string $prefix Prefix for the operation
     * @param string $organizationCode Organization code for data isolation
     * @param array $objectKeys Array of object keys to delete
     * @param StorageBucketType $bucketType Storage bucket type
     * @param array $options Additional options
     * @return array Delete result with success and error information
     */
    public function deleteObjectsByCredential(
        string $prefix,
        string $organizationCode,
        array $objectKeys,
        StorageBucketType $bucketType = StorageBucketType::Private,
        array $options = []
    ): array {
        return $this->cloudFileRepository->deleteObjectsByCredential(
            $prefix,
            $organizationCode,
            $objectKeys,
            $bucketType,
            $options
        );
    }

    public function getFullPrefix(string $organizationCode): string
    {
        return $this->cloudFileRepository->getFullPrefix($organizationCode);
    }

    public function generateWorkDir(string $userId, int $projectId, string $code = 'be-delightful', string $lastPath = 'project'): string
    {
        return $this->cloudFileRepository->generateWorkDir($userId, $projectId, $code, $lastPath);
    }

    public function getFullWorkDir(string $organizationCode, string $userId, int $projectId, string $code = 'be-delightful', string $lastPath = 'project'): string
    {
        $prefix = $this->getFullPrefix($organizationCode);
        # judgemostnextcharacterwhetheris /,ifis,godrop
        if (substr($prefix, -1) === '/') {
            $prefix = substr($prefix, 0, -1);
        }
        $workDir = $this->generateWorkDir($userId, $projectId, $code, $lastPath);
        return $prefix . $workDir;
    }

    public function setHeadObjectByCredential(
        string $organizationCode,
        string $objectKey,
        array $metadata,
        StorageBucketType $bucketType = StorageBucketType::Private,
        array $options = []
    ): void {
        $this->cloudFileRepository->setHeadObjectByCredential($organizationCode, $objectKey, $metadata, $bucketType, $options);
    }

    /**
     * fromcloudstoragegetfilelist.
     *
     * @param string $organizationCode organizationencoding
     * @param string $directoryPrefix directoryfrontsuffix
     * @param StorageBucketType $bucketType storagebuckettype
     * @return CloudFileInfoDTO[] fileDTOobjectarray
     */
    public function getFilesFromCloudStorage(
        string $organizationCode,
        string $directoryPrefix,
        StorageBucketType $bucketType = StorageBucketType::Private
    ): array {
        // uselistObjectsByCredentialcolumnoutdirectoryfile
        $objectsResponse = $this->cloudFileRepository->listObjectsByCredential(
            $organizationCode,
            $directoryPrefix,
            $bucketType
        );

        $files = [];

        // correctparseobjectlistdatastructure
        $objectsList = $objectsResponse['objects'] ?? $objectsResponse;

        foreach ($objectsList as $object) {
            $objectKey = $object['key'] ?? $object['Key'] ?? '';
            $filename = basename($objectKey);

            $files[] = new CloudFileInfoDTO(
                key: $objectKey,
                filename: $filename,
                size: $object['size'] ?? null,
                lastModified: null // ASRbusinessmiddlenotusethefield,directlypassnull
            );
        }
        return $files;
    }
}
