<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel\Driver;

use Delightful\CloudFile\Kernel\Exceptions\ChunkDownloadException;
use Delightful\CloudFile\Kernel\Struct\ChunkDownloadConfig;
use Delightful\CloudFile\Kernel\Struct\CredentialPolicy;
use Delightful\CloudFile\Kernel\Struct\FileLink;
use Delightful\CloudFile\Kernel\Struct\FileMetadata;
use Delightful\CloudFile\Kernel\Struct\FilePreSignedUrl;

interface ExpandInterface
{
    public function getUploadCredential(CredentialPolicy $credentialPolicy, array $options = []): array;

    /**
     * @return array<string, FilePreSignedUrl>
     */
    public function getPreSignedUrls(array $fileNames, int $expires = 3600, array $options = []): array;

    /**
     * @return array<FileMetadata>
     */
    public function getMetas(array $paths, array $options = []): array;

    /**
     * @return array<FileLink>
     */
    public function getFileLinks(array $paths, array $downloadNames = [], int $expires = 3600, array $options = []): array;

    public function destroy(array $paths, array $options = []): void;

    public function duplicate(string $source, string $destination, array $options = []): string;

    /**
     * Download file by chunks.
     *
     * @param string $filePath Remote file path
     * @param string $localPath Local file path to save
     * @param ChunkDownloadConfig $config Download configuration
     * @param array $options Additional options
     * @throws ChunkDownloadException
     */
    public function downloadByChunks(string $filePath, string $localPath, ChunkDownloadConfig $config, array $options = []): void;
}
