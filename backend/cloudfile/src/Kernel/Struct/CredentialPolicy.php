<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel\Struct;

class CredentialPolicy
{
    /**
     * File size limit.
     */
    private int $sizeMax = 0;

    /**
     * Credential validity period.
     */
    private int $expires = 7200;

    /**
     * Allowed upload file types.
     */
    private array $mimeType = [];

    /**
     * Upload to specified directory.
     */
    private string $dir = '';

    /**
     * Whether to enable STS mode.
     * Get temporary credentials for frontend use.
     */
    private bool $sts = false;

    /**
     * Role session name.
     * Used in STS mode.
     * Can be used to record the operator.
     */
    private string $roleSessionName = '';

    /**
     * STS type.
     * Used in STS mode.
     */
    private string $stsType = '';

    private string $contentType = '';

    public function __construct(array $config = [])
    {
        if (isset($config['size_max'])) {
            $this->sizeMax = (int) $config['size_max'];
        }
        if (isset($config['expires'])) {
            $this->expires = (int) $config['expires'];
        }
        if (isset($config['mime_type'])) {
            $this->mimeType = (array) $config['mime_type'];
        }
        if (isset($config['dir'])) {
            $this->dir = $this->formatDirPath($config['dir']);
        }
        if (isset($config['sts'])) {
            $this->sts = (bool) $config['sts'];
        }
        if (isset($config['role_session_name'])) {
            $this->roleSessionName = (string) $config['role_session_name'];
        }
        if (isset($config['sts_type'])) {
            $this->stsType = (string) $config['sts_type'];
        }
        if (isset($config['content_type'])) {
            $this->contentType = (string) $config['content_type'];
        }
    }

    public function getSizeMax(): int
    {
        return $this->sizeMax;
    }

    public function getExpires(): int
    {
        return $this->expires;
    }

    public function getMimeType(): array
    {
        return $this->mimeType;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function isSts(): bool
    {
        return $this->sts;
    }

    public function getRoleSessionName(): string
    {
        return $this->roleSessionName;
    }

    public function getStsType(): string
    {
        return $this->stsType;
    }

    public function setSts(bool $sts): void
    {
        $this->sts = $sts;
    }

    public function setStsType(string $stsType): void
    {
        $this->stsType = $stsType;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function uniqueKey(array $options = []): string
    {
        return md5(serialize($this) . serialize($options));
    }

    /**
     * Remove extra / from left and right, remove empty /, end with /.
     */
    private function formatDirPath(string $path): string
    {
        if ($path === '') {
            return '';
        }
        return implode('/', array_filter(explode('/', $path))) . '/';
    }
}
