<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel;

use BeDelightful\CloudFile\Kernel\Driver\ExpandInterface;
use BeDelightful\CloudFile\Kernel\Driver\FileService\FileServiceApi;
use BeDelightful\CloudFile\Kernel\Exceptions\ChunkDownloadException;
use BeDelightful\CloudFile\Kernel\Exceptions\CloudFileException;
use BeDelightful\CloudFile\Kernel\Struct\AppendUploadFile;
use BeDelightful\CloudFile\Kernel\Struct\ChunkDownloadConfig;
use BeDelightful\CloudFile\Kernel\Struct\ChunkUploadFile;
use BeDelightful\CloudFile\Kernel\Struct\CredentialPolicy;
use BeDelightful\CloudFile\Kernel\Struct\FileLink;
use BeDelightful\CloudFile\Kernel\Struct\FileMetadata;
use BeDelightful\CloudFile\Kernel\Struct\FilePreSignedUrl;
use BeDelightful\CloudFile\Kernel\Struct\UploadFile;
use BeDelightful\CloudFile\Kernel\Utils\SimpleUpload;
use BeDelightful\CloudFile\Kernel\Utils\SimpleUpload\AliyunSimpleUpload;
use BeDelightful\CloudFile\Kernel\Utils\SimpleUpload\FileServiceSimpleUpload;
use BeDelightful\CloudFile\Kernel\Utils\SimpleUpload\ObsSimpleUpload;
use BeDelightful\CloudFile\Kernel\Utils\SimpleUpload\TosSimpleUpload;
use BeDelightful\SdkBase\SdkBase;
use Hyperf\Stringable\Str;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\PathNormalizer;

class FilesystemProxy extends Filesystem
{
    private string $adapterName;

    private ExpandInterface $expand;

    private SdkBase $container;

    private array $config;

    private bool $isPublicRead = false;

    private string $publicDomain = '';

    private array $options = [];

    private array $simpleUploadsMap = [
        AdapterName::ALIYUN => AliyunSimpleUpload::class,
        AdapterName::TOS => TosSimpleUpload::class,
        AdapterName::OBS => ObsSimpleUpload::class,
        AdapterName::FILE_SERVICE => FileServiceSimpleUpload::class,
    ];

    private array $simpleUploadInstances = [];

    public function __construct(
        SdkBase $container,
        string $adapterName,
        FilesystemAdapter $adapter,
        array $config = [],
        ?PathNormalizer $pathNormalizer = null
    ) {
        $this->container = $container;
        $this->adapterName = AdapterName::form($adapterName);
        $this->config = $config;
        $this->expand = $this->createExpand($this->adapterName, $config);
        $this->initSimpleUpload();
        parent::__construct($adapter, $config, $pathNormalizer);
    }

    /**
     * Upload file.
     */
    public function upload(UploadFile $uploadFile, array $config = []): string
    {
        $key = $uploadFile->getKeyPath();

        $stream = fopen($uploadFile->getRealPath(), 'r+');
        if (! is_resource($stream)) {
            throw new CloudFileException("file stream is not resource | [{$uploadFile->getName()}]");
        }
        $contents = '';
        while (! feof($stream)) {
            $contents .= fread($stream, 8192);
        }
        fclose($stream);
        $this->write($key, $contents, $config);
        $uploadFile->setKey($key);
        return $key;
    }

    /**
     * Upload file - via temporary credentials direct upload.
     */
    public function uploadByCredential(UploadFile $uploadFile, CredentialPolicy $credentialPolicy, array $options = []): void
    {
        $credentialPolicy->setSts(false);
        $credentialPolicy->setContentType($uploadFile->getMimeType());
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);
        $this->getSimpleUploadInstance($this->adapterName)->uploadObject($credential, $uploadFile);
        $uploadFile->release();
    }

    /**
     * Upload file by chunks using STS credentials.
     *
     * @param ChunkUploadFile $chunkUploadFile Chunk upload file object
     * @param CredentialPolicy $credentialPolicy Credential policy
     * @param array $options Additional options
     * @throws CloudFileException
     */
    public function uploadByChunks(ChunkUploadFile $chunkUploadFile, CredentialPolicy $credentialPolicy, array $options = []): void
    {
        // Force STS mode for chunk upload
        $credentialPolicy->setSts(true);
        $credentialPolicy->setContentType($chunkUploadFile->getMimeType());

        // Get STS temporary credentials
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);

        // Call platform-specific chunk upload implementation
        $this->getSimpleUploadInstance($this->adapterName)->uploadObjectByChunks($credential, $chunkUploadFile);

        // Release file resources
        $chunkUploadFile->release();
    }

    /**
     * Append upload file - via temporary credentials direct upload.
     */
    public function appendUploadByCredential(AppendUploadFile $appendUploadFile, CredentialPolicy $credentialPolicy, array $options = []): void
    {
        $credentialPolicy->setSts(true);
        $credentialPolicy->setContentType($appendUploadFile->getMimeType());
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);
        $this->getSimpleUploadInstance($this->adapterName)->appendUploadObject($credential, $appendUploadFile);
        $appendUploadFile->release();
    }

    /**
     * List objects - via temporary credentials.
     *
     * @param CredentialPolicy $credentialPolicy Credential policy
     * @param string $prefix Object prefix filter
     * @param array $options Additional options (marker, max-keys, etc.)
     * @return array Object list
     */
    public function listObjectsByCredential(CredentialPolicy $credentialPolicy, string $prefix = '', array $options = []): array
    {
        $credentialPolicy->setSts(true);
        $credentialPolicy->setStsType('list_objects');
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);
        return $this->getSimpleUploadInstance($this->adapterName)->listObjectsByCredential($credential, $prefix, $options);
    }

    /**
     * Delete object - via temporary credentials.
     *
     * @param CredentialPolicy $credentialPolicy Credential policy
     * @param string $objectKey Object key to delete
     * @param array $options Additional options
     */
    public function deleteObjectByCredential(CredentialPolicy $credentialPolicy, string $objectKey, array $options = []): void
    {
        $credentialPolicy->setSts(true);
        $credentialPolicy->setStsType('del_objects');
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);
        $object = $this->getSimpleUploadInstance($this->adapterName);
        $object->deleteObjectByCredential($credential, $objectKey, $options);
    }

    /**
     * Copy object - via temporary credentials.
     *
     * @param CredentialPolicy $credentialPolicy Credential policy
     * @param string $sourceKey Source object key
     * @param string $destinationKey Destination object key
     * @param array $options Additional options
     */
    public function copyObjectByCredential(CredentialPolicy $credentialPolicy, string $sourceKey, string $destinationKey, array $options = []): void
    {
        $credentialPolicy->setSts(true);
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);
        $this->getSimpleUploadInstance($this->adapterName)->copyObjectByCredential($credential, $sourceKey, $destinationKey, $options);
    }

    /**
     * Get upload temporary credential
     */
    public function getUploadTemporaryCredential(CredentialPolicy $credentialPolicy, array $options = []): array
    {
        $isCache = (bool) ($options['cache'] ?? true);
        $cacheKey = $credentialPolicy->uniqueKey($options);
        if ($isCache && $data = $this->getCache($cacheKey)) {
            return $data;
        }
        $credential = $this->expand->getUploadCredential($credentialPolicy, $options);

        $platform = $credential['platform'] ?? $this->adapterName;
        $expires = $credential['expires'] ?? time() + $credentialPolicy->getExpires();
        $temporaryCredential = $credential['temporary_credential'] ?? $credential;
        $data = [
            'platform' => $platform,
            'temporary_credential' => $temporaryCredential,
            'expires' => (int) $expires,
        ];
        $cacheTtl = max(0, (int) ($expires - time() - 60));
        $cacheTtl = max($cacheTtl, 1);
        $this->setCache($cacheKey, $data, $cacheTtl);
        return $data;
    }

    /**
     * @return array<string, FilePreSignedUrl>
     */
    public function getPreSignedUrls(array $fileNames, int $expires = 3600, array $options = []): array
    {
        return $this->expand->getPreSignedUrls($fileNames, $expires, $options);
    }

    /**
     * Get file metadata.
     * @return array<FileMetadata>
     */
    public function getMetas(array $paths, array $options = []): array
    {
        return $this->expand->getMetas($this->formatPaths($paths), $options);
    }

    /**
     * Get file links.
     * @return array<FileLink>
     */
    public function getLinks(array $paths, array $downloadNames = [], int $expires = 3600, array $options = []): array
    {
        $paths = $this->formatPaths($paths);
        $platform = $this->config['platform'] ?? '';
        // If public read, directly return concatenated link
        $list = [];
        if ($this->isPublicRead && ! empty($this->publicDomain) && empty($downloadNames)) {
            foreach ($paths as $path) {
                if ($this->adapterName === AdapterName::FILE_SERVICE && $platform === 'minio') {
                    $uri = $this->publicDomain . '/' . $this->config['key'] . '/' . $path;
                } else {
                    $uri = $this->publicDomain . '/' . $path;
                }
                $list[$path] = new FileLink($path, $uri, $expires);
            }
            return $list;
        }
        $isCache = (bool) ($options['cache'] ?? true);
        unset($options['cache']);
        $unCachePaths = $paths;
        if ($isCache) {
            foreach ($paths as $path) {
                $cacheKey = md5($path . serialize($downloadNames[$path] ?? '') . $expires . serialize($options));
                if ($data = $this->getCache($cacheKey)) {
                    if ($data instanceof FileLink) {
                        $list[$path] = $data;
                        $unCachePaths = array_diff($unCachePaths, [$path]);
                    }
                }
            }
        }
        if (! empty($unCachePaths)) {
            $unCachePaths = array_values($unCachePaths);
            $unCachePaths = $this->filterMinioPaths($unCachePaths);
            $unCachePathsData = $this->expand->getFileLinks($unCachePaths, $downloadNames, $expires, $options);
            foreach ($unCachePathsData as $path => $data) {
                if (! $data instanceof FileLink) {
                    continue;
                }
                if ($this->isPublicRead) {
                    $noSignUrlParsed = parse_url($data->getUrl());
                    $port = '';
                    if (! empty($noSignUrlParsed['port'])) {
                        $port = ':' . $noSignUrlParsed['port'];
                    }
                    $noSignUrl = $noSignUrlParsed['scheme'] . '://' . $noSignUrlParsed['host'] . $port . $noSignUrlParsed['path'];
                    $data->setUrl($noSignUrl);
                    // Set public domain on first load
                    $this->publicDomain = $noSignUrlParsed['scheme'] . '://' . $noSignUrlParsed['host'] . $port;
                }
                $list[$path] = $data;
                $cacheKey = md5($path . serialize($downloadNames[$path] ?? '') . $expires . serialize($options));
                $this->setCache($cacheKey, $data, $expires - 60);
            }
        }
        return $list;
    }

    /**
     * Delete file.
     */
    public function destroy(array $paths, array $options = []): void
    {
        $this->expand->destroy($paths, $options);
    }

    /**
     * Copy file.
     */
    public function duplicate(string $source, string $destination, array $options = []): string
    {
        return $this->expand->duplicate($source, $destination, $options);
    }

    /**
     * Get object metadata - via temporary credentials.
     *
     * @param CredentialPolicy $credentialPolicy Credential policy
     * @param string $objectKey Object key
     * @param array $options Additional options
     * @return array Object metadata
     * @throws CloudFileException
     */
    public function getHeadObjectByCredential(CredentialPolicy $credentialPolicy, string $objectKey, array $options = []): array
    {
        $credentialPolicy->setSts(true);
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);
        return $this->getSimpleUploadInstance($this->adapterName)->getHeadObjectByCredential($credential, $objectKey, $options);
    }

    /**
     * Create object - via temporary credentials.
     *
     * @param CredentialPolicy $credentialPolicy Credential policy
     * @param string $objectKey Object key
     * @param array $options Additional options (content, content_type, etc.)
     */
    public function createObjectByCredential(CredentialPolicy $credentialPolicy, string $objectKey, array $options = []): void
    {
        $credentialPolicy->setSts(true);
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);
        $this->getSimpleUploadInstance($this->adapterName)->createObjectByCredential($credential, $objectKey, $options);
    }

    /**
     * Get pre-signed URL - via temporary credentials.
     *
     * @param CredentialPolicy $credentialPolicy Credential policy
     * @param string $objectKey Object key
     * @param array $options Additional options (method, expires, filename, etc.)
     * @return string Pre-signed URL
     */
    public function getPreSignedUrlByCredential(CredentialPolicy $credentialPolicy, string $objectKey, array $options = []): string
    {
        $credentialPolicy->setSts(true);
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);
        return $this->getSimpleUploadInstance($this->adapterName)->getPreSignedUrlByCredential($credential, $objectKey, $options);
    }

    /**
     * Delete multiple objects by credential.
     *
     * @param CredentialPolicy $credentialPolicy Credential policy
     * @param array $objectKeys Array of object keys to delete
     * @param array $options Additional options
     * @return array Deletion result, including success and error information
     */
    public function deleteObjectsByCredential(CredentialPolicy $credentialPolicy, array $objectKeys, array $options = []): array
    {
        $credentialPolicy->setSts(true);
        $credentialPolicy->setStsType('del_objects');
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);
        return $this->getSimpleUploadInstance($this->adapterName)->deleteObjectsByCredential($credential, $objectKeys, $options);
    }

    /**
     * Set object metadata by credential.
     *
     * @param CredentialPolicy $credentialPolicy Credential policy
     * @param string $objectKey Object key
     * @param array $metadata Metadata to set
     * @param array $options Additional options
     */
    public function setHeadObjectByCredential(CredentialPolicy $credentialPolicy, string $objectKey, array $metadata, array $options = []): void
    {
        $credentialPolicy->setSts(true);
        $credentialPolicy->setStsType('set_object_meta');
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);
        $this->getSimpleUploadInstance($this->adapterName)->setHeadObjectByCredential($credential, $objectKey, $metadata, $options);
    }

    /**
     * Download file by chunks.
     *
     * @param string $filePath Remote file path
     * @param string $localPath Local file path to save
     * @param null|ChunkDownloadConfig $config Download configuration
     * @param array $options Additional options
     * @throws ChunkDownloadException
     */
    public function downloadByChunks(string $filePath, string $localPath, ?ChunkDownloadConfig $config = null, array $options = []): void
    {
        $credentialPolicy = new CredentialPolicy([
            'sts' => true,
            'role_session_name' => 'delightful',
            'dir' => '',
        ]);

        $platform = $this->config['platform'] ?? $this->adapterName;
        $credential = $this->getUploadTemporaryCredential($credentialPolicy, $options);
        $expandConfig = $this->createExpandConfigByCredential($platform, $credential);
        $config = $config ?? ChunkDownloadConfig::createDefault();
        $expandObject = $this->createExpand($platform, $expandConfig);
        $expandObject->downloadByChunks($filePath, $localPath, $config, $options);
    }

    public function setIsPublicRead(bool $isPublicRead): void
    {
        $this->isPublicRead = $isPublicRead;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    protected function initSimpleUpload(): void
    {
        foreach ($this->simpleUploadsMap as $platform => $simpleUploadClass) {
            if (! isset($this->simpleUploadInstances[$platform])) {
                $this->simpleUploadInstances[$platform] = new $simpleUploadClass($this->container);
            }
        }
    }

    protected function getSimpleUploadInstance(string $platform): SimpleUpload
    {
        if (! isset($this->simpleUploadInstances[$platform])) {
            throw new CloudFileException("adapter not found | [{$this->adapterName}]");
        }
        return $this->simpleUploadInstances[$platform];
    }

    private function setCache(string $key, $value, int $ttl): void
    {
        $this->container->getCache()->set($this->uniqueKey() . '_' . $key, $value, $ttl);
    }

    private function getCache(string $key): mixed
    {
        return $this->container->getCache()->get($this->uniqueKey() . '_' . $key);
    }

    private function formatPaths(array $paths): array
    {
        $filePaths = [];
        foreach ($paths as $path) {
            if (str_contains($path, '%')) {
                $path = str_replace('%', '%25', $path);
            }
            $filePaths[] = $path;
        }
        return $filePaths;
    }

    private function createExpand(string $adapterName, array $config = []): ExpandInterface
    {
        switch ($adapterName) {
            case AdapterName::ALIYUN:
                return new Driver\OSS\OSSExpand($config);
            case AdapterName::TOS:
                return new Driver\TOS\TOSExpand($config);
            case AdapterName::FILE_SERVICE:
                $fileServiceApi = new FileServiceApi($this->container, $config);
                return new Driver\FileService\FileServiceExpand($fileServiceApi);
            case AdapterName::LOCAL:
                return new Driver\Local\LocalExpand($config);
            default:
                throw new CloudFileException("expand not found | [{$adapterName}]");
        }
    }

    private function createExpandConfigByCredential(string $adapterName, array $credential): array
    {
        switch ($adapterName) {
            case AdapterName::TOS:
                $tempCred = $credential['temporary_credential'];
                $credentials = $tempCred['credentials'];
                return [
                    'region' => $tempCred['region'],
                    'endpoint' => $tempCred['endpoint'],
                    'ak' => $credentials['AccessKeyId'],
                    'sk' => $credentials['SecretAccessKey'],
                    'securityToken' => $credentials['SessionToken'], // STS token for temporary access
                    'bucket' => $tempCred['bucket'],
                ];
            case AdapterName::ALIYUN:
                $temp = $credential['temporary_credential'];
                $region = $temp['region'];
                $actualRegion = str_replace('oss-', '', $region);
                return [
                    'accessId' => $temp['access_key_id'],
                    'accessSecret' => $temp['access_key_secret'],
                    'securityToken' => $temp['sts_token'],
                    'endpoint' => 'https://oss-' . $actualRegion . '.aliyuncs.com',
                    'bucket' => $temp['bucket'],
                    'timeout' => 3600,
                    'connectTimeout' => 10,
                ];
            default:
                throw new CloudFileException("expand not found | [{$adapterName}]");
        }
    }

    private function filterMinioPaths(array $paths): array
    {
        $key = $this->config['key'] ?? '';
        if ($this->adapterName !== AdapterName::FILE_SERVICE || $key !== 'minio') {
            return $paths;
        }
        $newPaths = [];
        foreach ($paths as $path) {
            if (Str::startsWith($path, $key)) {
                $newPaths[] = Str::replaceFirst($key . '/', '', $path);
            } else {
                $newPaths[] = $path;
            }
        }
        return $newPaths;
    }

    private function uniqueKey(): string
    {
        return 'cloudfile:' . md5($this->adapterName . serialize($this->config));
    }
}
