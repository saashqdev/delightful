<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Result;

use Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant\SandboxStatus;

/**
 * Batch sandbox status result
 * Handles batched sandbox status query results.
 */
class BatchStatusResult extends GatewayResult
{
    private array $sandboxStatuses = [];

    /**
     * Create batch status result from API response.
     */
    public static function fromApiResponse(array $response): self
    {
        $result = new self(
            $response['code'] ?? 2000,
            $response['message'] ?? 'Unknown error',
            $response['data'] ?? []
        );

        // Parse batch status data; per docs, data is directly the sandbox status array
        $data = $response['data'] ?? [];
        if (is_array($data)) {
            $result->sandboxStatuses = $data;
        }

        return $result;
    }

    /**
     * Get all sandbox statuses
     */
    public function getSandboxStatuses(): array
    {
        return $this->sandboxStatuses ?: $this->getData();
    }

    /**
     * Get status for a specific sandbox
     */
    public function getSandboxStatus(string $sandboxId): ?string
    {
        $statuses = $this->getSandboxStatuses();

        foreach ($statuses as $sandbox) {
            if (isset($sandbox['sandbox_id']) && $sandbox['sandbox_id'] === $sandboxId) {
                return $sandbox['status'] ?? null;
            }
        }

        return null;
    }

    /**
     * Check whether a specific sandbox is running.
     */
    public function isSandboxRunning(string $sandboxId): bool
    {
        $status = $this->getSandboxStatus($sandboxId);
        return $status !== null && SandboxStatus::isAvailable($status);
    }

    /**
     * Get list of running sandboxes.
     */
    public function getRunningSandboxes(): array
    {
        $running = [];
        $statuses = $this->getSandboxStatuses();

        foreach ($statuses as $sandbox) {
            if (isset($sandbox['status']) && SandboxStatus::isAvailable($sandbox['status'])) {
                $running[] = $sandbox;
            }
        }

        return $running;
    }

    /**
     * Get IDs of running sandboxes.
     */
    public function getRunningSandboxIds(): array
    {
        $ids = [];
        $running = $this->getRunningSandboxes();

        foreach ($running as $sandbox) {
            if (isset($sandbox['sandbox_id'])) {
                $ids[] = $sandbox['sandbox_id'];
            }
        }

        return $ids;
    }

    /**
     * Get total sandbox count.
     */
    public function getTotalCount(): int
    {
        return count($this->getSandboxStatuses());
    }

    /**
     * Get running sandbox count.
     */
    public function getRunningCount(): int
    {
        return count($this->getRunningSandboxes());
    }
}
