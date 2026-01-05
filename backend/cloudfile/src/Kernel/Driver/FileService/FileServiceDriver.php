<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel\Driver\FileService;

use Delightful\CloudFile\Kernel\Exceptions\CloudFileException;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;

class FileServiceDriver implements FilesystemAdapter
{
    private FileServiceApi $fileServiceApi;

    public function __construct(FileServiceApi $fileServiceApi)
    {
        $this->fileServiceApi = $fileServiceApi;
    }

    public function getFileServiceApi(): FileServiceApi
    {
        return $this->fileServiceApi;
    }

    public function fileExists(string $path): bool
    {
        throw new CloudFileException('Not supported yet');
    }

    public function write(string $path, string $contents, Config $config): void
    {
        throw new CloudFileException('Not supported yet');
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        throw new CloudFileException('Not supported yet');
    }

    public function read(string $path): string
    {
        throw new CloudFileException('Not supported yet');
    }

    public function delete(string $path): void
    {
        throw new CloudFileException('Not supported yet');
    }

    public function fileSize(string $path): FileAttributes
    {
        throw new CloudFileException('Not supported yet');
    }

    public function move(string $source, string $destination, Config $config): void
    {
        throw new CloudFileException('Not supported yet');
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        throw new CloudFileException('Not supported yet');
    }

    public function readStream(string $path)
    {
        throw new CloudFileException('Not supported yet');
    }

    public function deleteDirectory(string $path): void
    {
        throw new CloudFileException('Not supported yet');
    }

    public function createDirectory(string $path, Config $config): void
    {
        throw new CloudFileException('Not supported yet');
    }

    public function setVisibility(string $path, string $visibility): void
    {
        throw new CloudFileException('Not supported yet');
    }

    public function visibility(string $path): FileAttributes
    {
        throw new CloudFileException('Not supported yet');
    }

    public function mimeType(string $path): FileAttributes
    {
        throw new CloudFileException('Not supported yet');
    }

    public function lastModified(string $path): FileAttributes
    {
        throw new CloudFileException('Not supported yet');
    }

    public function listContents(string $path, bool $deep): iterable
    {
        throw new CloudFileException('Not supported yet');
    }
}
