<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\SandboxStatus;

/**
 * Sandbox status result
 * Handles single sandbox status query results.
 */
class SandboxStatusResult extends GatewayResult
{
    private ?string $sandboxId = null;

    private ?string $status = null;

    /**
     * Create sandbox status result from API response.
     */
    public static function fromApiResponse(array $response): self
    {
        $result = new self(
            $response['code'] ?? 2000,
            $response['message'] ?? 'Unknown error',
            $response['data'] ?? []
        );

        // Parse sandbox status data
        $data = $response['data'] ?? [];
        if (isset($data['sandbox_id'])) {
            $result->sandboxId = $data['sandbox_id'];
        }
        if (isset($data['status'])) {
            $result->status = $data['status'];
        }

        return $result;
    }

    /**
     * Get sandbox ID.
     */
    public function getSandboxId(): ?string
    {
        return $this->sandboxId ?? $this->getDataValue('sandbox_id');
    }

    /**
     * Get sandbox status
     */
    public function getStatus(): ?string
    {
        return $this->status ?? $this->getDataValue('status');
    }

    /**
     * Set sandbox ID.
     */
    public function setSandboxId(?string $sandboxId): self
    {
        $this->sandboxId = $sandboxId;
        return $this;
    }

    /**
     * Set sandbox status
     */
    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Check whether the sandbox is running.
     */
    public function isRunning(): bool
    {
        $status = $this->getStatus();
        return $status !== null && SandboxStatus::isAvailable($status);
    }

    /**
     * Check whether the sandbox is pending.
     */
    public function isPending(): bool
    {
        return $this->getStatus() === SandboxStatus::PENDING;
    }

    /**
     * Check whether the sandbox is exited.
     */
    public function isExited(): bool
    {
        return $this->getStatus() === SandboxStatus::EXITED;
    }

    /**
     * Check whether the status is valid.
     */
    public function hasValidStatus(): bool
    {
        $status = $this->getStatus();
        return $status !== null && SandboxStatus::isValidStatus($status);
    }
}
