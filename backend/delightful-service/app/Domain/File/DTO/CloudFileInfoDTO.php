<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\File\DTO;

/**
 * 云storagefileinformationDTO.
 */
readonly class CloudFileInfoDTO
{
    public function __construct(
        private string $key,
        private string $filename,
        private ?int $size = null,
        private ?string $lastModified = null
    ) {
    }

    /**
     * getfilekey.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * getfile名.
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * getfilesize.
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * getmostbackmodification time.
     */
    public function getLastModified(): ?string
    {
        return $this->lastModified;
    }

    /**
     * fromarraycreateDTO.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'] ?? '',
            filename: $data['filename'] ?? '',
            size: $data['size'] ?? null,
            lastModified: $data['last_modified'] ?? null
        );
    }

    /**
     * convert为array（tobackcompatible）.
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'filename' => $this->filename,
            'size' => $this->size,
            'last_modified' => $this->lastModified,
        ];
    }
}
