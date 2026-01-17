<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\FileConverter\Response;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Contract\ResponseInterface;
use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result\GatewayResult;

/**
 * File conversion response.
 */
class FileConverterResponse implements ResponseInterface
{
    private bool $success;

    private int $code;

    private string $message;

    private ConverterDataDTO $data;

    public function __construct(bool $success, int $code, string $message, array $data = [])
    {
        $this->success = $success;
        $this->code = $code;
        $this->message = $message;
        $this->data = ConverterDataDTO::fromArray($data);
    }

    public static function fromGatewayResult(GatewayResult $result): self
    {
        return new self(
            $result->isSuccess(),
            $result->getCode(),
            $result->getMessage(),
            $result->getData()
        );
    }

    public static function fromApiResponse(array $response): self
    {
        return new self(
            ($response['code'] ?? -1) === 1000,
            $response['code'] ?? -1,
            $response['message'] ?? 'Unknown error',
            $response['data'] ?? []
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data->toArray();
    }

    public function getDataDTO(): ConverterDataDTO
    {
        return $this->data;
    }

    public function getBatchId(): ?string
    {
        return $this->data->batchId;
    }

    /**
     * @return FileItemDTO[]
     */
    public function getConvertedFiles(): array
    {
        return $this->data->files;
    }

    public function getOssKeys(): array
    {
        return array_map(
            fn (FileItemDTO $file) => $file->ossKey,
            $this->getConvertedFiles()
        );
    }

    /**
     * Get total file count.
     */
    public function getTotalFiles(): int
    {
        return $this->data->totalFiles;
    }

    /**
     * Get successful conversion count.
     */
    public function getSuccessCount(): int
    {
        return $this->data->successCount;
    }

    /**
     * Get conversion rate.
     */
    public function getConversionRate(): ?float
    {
        return $this->data->conversionRate;
    }
}
