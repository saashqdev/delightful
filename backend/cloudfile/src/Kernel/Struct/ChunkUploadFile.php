<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Kernel\Struct;

use ReflectionClass;

/**
 * Chunk upload file class
 * Extends UploadFile, adds chunk upload related configuration.
 */
class ChunkUploadFile extends UploadFile
{
    private ChunkUploadConfig $chunkConfig;

    private ?ChunkProgressCallback $progressCallback = null;

    private string $uploadId = '';

    /**
     * @var ChunkInfo[] Chunk information list
     */
    private array $chunks = [];

    public function __construct(
        string $realPath,
        string $dir = '',
        string $name = '',
        bool $rename = true,
        ?ChunkUploadConfig $chunkConfig = null
    ) {
        parent::__construct($realPath, $dir, $name, $rename);
        $this->chunkConfig = $chunkConfig ?? new ChunkUploadConfig();
    }

    public function getChunkConfig(): ChunkUploadConfig
    {
        return $this->chunkConfig;
    }

    public function setChunkConfig(ChunkUploadConfig $chunkConfig): void
    {
        $this->chunkConfig = $chunkConfig;
    }

    public function getProgressCallback(): ?ChunkProgressCallback
    {
        return $this->progressCallback;
    }

    public function setProgressCallback(?ChunkProgressCallback $progressCallback): void
    {
        $this->progressCallback = $progressCallback;
    }

    public function getUploadId(): string
    {
        return $this->uploadId;
    }

    public function setUploadId(string $uploadId): void
    {
        $this->uploadId = $uploadId;
    }

    /**
     * @return ChunkInfo[]
     */
    public function getChunks(): array
    {
        return $this->chunks;
    }

    /**
     * @param ChunkInfo[] $chunks
     */
    public function setChunks(array $chunks): void
    {
        $this->chunks = $chunks;
    }

    public function addChunk(ChunkInfo $chunk): void
    {
        $this->chunks[] = $chunk;
    }

    /**
     * Calculate chunk information.
     */
    public function calculateChunks(): void
    {
        $fileSize = $this->getSize();
        $chunkSize = $this->chunkConfig->getChunkSize();

        $chunks = [];
        $chunkCount = (int) ceil($fileSize / $chunkSize);

        for ($i = 0; $i < $chunkCount; ++$i) {
            $partNumber = $i + 1;
            $start = $i * $chunkSize;
            $end = min($start + $chunkSize - 1, $fileSize - 1);
            $size = $end - $start + 1;

            $chunks[] = new ChunkInfo($partNumber, $start, $end, $size);
        }

        $this->chunks = $chunks;
    }

    /**
     * Check if chunk upload is needed.
     */
    public function shouldUseChunkUpload(): bool
    {
        return $this->getSize() > $this->chunkConfig->getThreshold();
    }

    /**
     * Trigger progress callback.
     */
    public function triggerProgress(int $uploadedChunks, int $totalChunks, int $uploadedBytes, int $totalBytes): void
    {
        if ($this->progressCallback) {
            $this->progressCallback->onProgress($uploadedChunks, $totalChunks, $uploadedBytes, $totalBytes);
        }
    }

    /**
     * Create ChunkUploadFile from UploadFile.
     *
     * @param UploadFile $uploadFile Source upload file
     * @param null|ChunkUploadConfig $chunkConfig Chunk upload configuration
     */
    public static function fromUploadFile(UploadFile $uploadFile, ?ChunkUploadConfig $chunkConfig = null): self
    {
        // Access the rename property through reflection since it's private
        $reflection = new ReflectionClass($uploadFile);
        $renameProperty = $reflection->getProperty('rename');
        $renameProperty->setAccessible(true);
        $rename = $renameProperty->getValue($uploadFile);

        $chunkUploadFile = new self(
            $uploadFile->getRealPath(),
            $uploadFile->getDir(),
            $uploadFile->getName(),
            $rename,
            $chunkConfig
        );

        // Copy any additional properties that might have been set
        if ($uploadFile->getKey()) {
            $chunkUploadFile->setKey($uploadFile->getKey());
        }

        return $chunkUploadFile;
    }
}
