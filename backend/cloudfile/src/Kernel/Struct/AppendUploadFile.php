<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel\Struct;

use BeDelightful\CloudFile\Kernel\Exceptions\CloudFileException;
use BeDelightful\CloudFile\Kernel\Utils\EasyFileTools;
use BeDelightful\CloudFile\Kernel\Utils\MimeTypes;

class AppendUploadFile
{
    private bool $isRemote = false;

    private string $remoteUrl = '';

    private string $name;

    private string $dir;

    private string $realPath;

    private string $mimeType;

    private int $size;

    private string $key = '';

    private int $position = 0;

    public function __construct(string $realPath, int $position = 0, string $dir = '', string $name = '')
    {
        $this->dir = $dir;
        $this->name = $name;
        $this->position = $position;
        if (EasyFileTools::isUrl($realPath) || EasyFileTools::isBase64Image($realPath)) {
            $this->isRemote = true;
            $this->remoteUrl = $realPath;
            return;
        }
        if (! is_file($realPath)) {
            throw new CloudFileException(sprintf('File not exists: %s', $realPath));
        }
        $this->realPath = $realPath;
        $this->size = filesize($realPath);
        $options = pathinfo($realPath);

        $this->name = $name ?: $options['basename'];
    }

    public function getKeyPath(): string
    {
        $prefix = '';
        if (! empty($this->dir)) {
            $prefix .= rtrim($this->dir, '/') . '/';
        }

        $prefix .= $this->name;
        return $prefix;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function getMimeType(): string
    {
        if (empty($this->mimeType)) {
            if ($this->isRemote) {
                $this->downloadRemoteUrl();
            } else {
                $this->mimeType = mime_content_type($this->realPath);
            }
        }
        return $this->mimeType;
    }

    public function getName(): string
    {
        if (empty($this->name) && $this->isRemote) {
            $this->downloadRemoteUrl();
        }
        return $this->name;
    }

    public function getRealPath(): string
    {
        if (empty($this->realPath) && $this->isRemote) {
            $this->downloadRemoteUrl();
        }
        return $this->realPath;
    }

    public function rename(): void
    {
        $this->name = uniqid() . '.' . pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function release(): void
    {
        if ($this->isRemote) {
            @unlink($this->realPath);
        }
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function getExt(): string
    {
        return MimeTypes::getExtension($this->getMimeType());
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    private function downloadRemoteUrl(): void
    {
        if (isset($this->realPath)) {
            return;
        }

        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'cloud—file-tmp-');
        // Open URL for reading, then open temporary file for writing
        $inputStream = fopen($this->remoteUrl, 'r');
        if (! $inputStream) {
            throw new CloudFileException(sprintf('Download remote file failed: %s', $this->remoteUrl));
        }
        $outputStream = fopen($tempFile, 'w');
        // Read input stream and write to output stream
        while ($data = fread($inputStream, 1024)) {
            fwrite($outputStream, $data);
        }
        // Close input and output streams
        fclose($inputStream);
        fclose($outputStream);

        $this->realPath = $tempFile;
        $this->size = filesize($this->realPath);
        $this->mimeType = mime_content_type($this->realPath);

        $path = parse_url($this->remoteUrl, PHP_URL_PATH);
        $this->name = pathinfo($path, PATHINFO_BASENAME);

        // Check if name has file extension, if not, generate one using mime_type
        if (empty(pathinfo($this->name, PATHINFO_EXTENSION))) {
            $this->name = $this->name . '.' . MimeTypes::getExtension($this->mimeType);
        }
    }
}
