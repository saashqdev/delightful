<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel\Utils\SimpleUpload;

use Delightful\CloudFile\Kernel\AdapterName;
use Delightful\CloudFile\Kernel\Exceptions\CloudFileException;
use Delightful\CloudFile\Kernel\Struct\AppendUploadFile;
use Delightful\CloudFile\Kernel\Struct\ChunkUploadFile;
use Delightful\CloudFile\Kernel\Struct\UploadFile;
use Delightful\CloudFile\Kernel\Utils\SimpleUpload;

class FileServiceSimpleUpload extends SimpleUpload
{
    protected array $simpleUploadsMap = [
        AdapterName::ALIYUN => AliyunSimpleUpload::class,
        AdapterName::TOS => TosSimpleUpload::class,
        AdapterName::OBS => ObsSimpleUpload::class,
        AdapterName::MINIO => S3SimpleUpload::class,
    ];

    /**
     * @var array<string, SimpleUpload>
     */
    protected array $simpleUploadInstances = [];

    public function uploadObject(array $credential, UploadFile $uploadFile): void
    {
        $simpleUpload = $this->getSimpleUpload($credential);
        $simpleUpload->uploadObject($credential, $uploadFile);
    }

    public function appendUploadObject(array $credential, AppendUploadFile $appendUploadFile): void
    {
        $simpleUpload = $this->getSimpleUpload($credential);
        $simpleUpload->appendUploadObject($credential, $appendUploadFile);
    }

    /**
     * Upload file in chunks
     * Forward request to specific platform implementation.
     *
     * @param array $credential Credential information
     * @param ChunkUploadFile $chunkUploadFile Chunk upload file object
     * @throws CloudFileException
     */
    public function uploadObjectByChunks(array $credential, ChunkUploadFile $chunkUploadFile): void
    {
        $simpleUpload = $this->getSimpleUpload($credential);
        $simpleUpload->uploadObjectByChunks($credential, $chunkUploadFile);
    }

    /**
     * List objects by credential
     * Forward request to specific platform implementation.
     *
     * @param array $credential Credential information
     * @param string $prefix Object prefix filter
     * @param array $options Additional options
     * @return array Object list
     * @throws CloudFileException
     */
    public function listObjectsByCredential(array $credential, string $prefix = '', array $options = []): array
    {
        $simpleUpload = $this->getSimpleUpload($credential);
        return $simpleUpload->listObjectsByCredential($credential, $prefix, $options);
    }

    /**
     * Delete object by credential
     * Forward request to specific platform implementation.
     *
     * @param array $credential Credential information
     * @param string $objectKey Object key to delete
     * @param array $options Additional options
     * @throws CloudFileException
     */
    public function deleteObjectByCredential(array $credential, string $objectKey, array $options = []): void
    {
        $simpleUpload = $this->getSimpleUpload($credential);
        $simpleUpload->deleteObjectByCredential($credential, $objectKey, $options);
    }

    /**
     * Copy object by credential
     * Forward request to specific platform implementation.
     *
     * @param array $credential Credential information
     * @param string $sourceKey Source object key
     * @param string $destinationKey Destination object key
     * @param array $options Additional options
     * @throws CloudFileException
     */
    public function copyObjectByCredential(array $credential, string $sourceKey, string $destinationKey, array $options = []): void
    {
        $simpleUpload = $this->getSimpleUpload($credential);
        $simpleUpload->copyObjectByCredential($credential, $sourceKey, $destinationKey, $options);
    }

    /**
     * Get object metadata by credential
     * Forward request to specific platform implementation.
     *
     * @param array $credential Credential information
     * @param string $objectKey Object key
     * @param array $options Additional options
     * @return array Object metadata
     * @throws CloudFileException
     */
    public function getHeadObjectByCredential(array $credential, string $objectKey, array $options = []): array
    {
        $simpleUpload = $this->getSimpleUpload($credential);
        return $simpleUpload->getHeadObjectByCredential($credential, $objectKey, $options);
    }

    /**
     * Create object by credential
     * Forward request to specific platform implementation.
     *
     * @param array $credential Credential information
     * @param string $objectKey Object key
     * @param array $options Additional options
     * @throws CloudFileException
     */
    public function createObjectByCredential(array $credential, string $objectKey, array $options = []): void
    {
        $simpleUpload = $this->getSimpleUpload($credential);
        $simpleUpload->createObjectByCredential($credential, $objectKey, $options);
    }

    /**
     * Generate pre-signed URL by credential
     * Forward request to specific platform implementation.
     *
     * @param array $credential Credential information
     * @param string $objectKey Object key
     * @param array $options Additional options (method, expires, filename, etc.)
     * @return string Pre-signed URL
     * @throws CloudFileException
     */
    public function getPreSignedUrlByCredential(array $credential, string $objectKey, array $options = []): string
    {
        $simpleUpload = $this->getSimpleUpload($credential);
        return $simpleUpload->getPreSignedUrlByCredential($credential, $objectKey, $options);
    }

    /**
     * Delete multiple objects by credential.
     *
     * @param array $credential Credential array
     * @param array $objectKeys Array of object keys to delete
     * @param array $options Additional options
     * @return array Delete result with success and error information
     * @throws CloudFileException
     */
    public function deleteObjectsByCredential(array $credential, array $objectKeys, array $options = []): array
    {
        $simpleUpload = $this->getSimpleUpload($credential);
        return $simpleUpload->deleteObjectsByCredential($credential, $objectKeys, $options);
    }

    /**
     * Set object metadata by credential.
     *
     * @param array $credential Credential array
     * @param string $objectKey Object key to set metadata
     * @param array $metadata Metadata to set
     * @param array $options Additional options
     * @throws CloudFileException
     */
    public function setHeadObjectByCredential(array $credential, string $objectKey, array $metadata, array $options = []): void
    {
        $simpleUpload = $this->getSimpleUpload($credential);
        $simpleUpload->setHeadObjectByCredential($credential, $objectKey, $metadata, $options);
    }

    private function getSimpleUpload(array $credential): SimpleUpload
    {
        $platform = $credential['platform'] ?? '';
        $platformCredential = $credential['temporary_credential'] ?? [];
        if (empty($platform) || empty($platformCredential)) {
            throw new CloudFileException('credential is empty');
        }

        if (! isset($this->simpleUploadsMap[$platform])) {
            throw new CloudFileException('platform is invalid');
        }

        if (! isset($this->simpleUploadInstances[$platform])) {
            $this->simpleUploadInstances[$platform] = new $this->simpleUploadsMap[$platform]($this->sdkContainer);
        }

        return $this->simpleUploadInstances[$platform];
    }
}
