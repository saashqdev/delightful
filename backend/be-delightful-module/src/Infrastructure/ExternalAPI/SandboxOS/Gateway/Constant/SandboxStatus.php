<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Gateway\Constant;

/**
 * Sandbox status constants
 * Status values defined by the sandbox communication spec
 */
class SandboxStatus
{
    /**
     * Sandbox pending state
     */
    public const PENDING = 'Pending';

    /**
     * Sandbox running state
     */
    public const RUNNING = 'Running';

    /**
     * Sandbox exited state
     */
    public const EXITED = 'Exited';

    /**
     * Sandbox unknown state
     */
    public const UNKNOWN = 'Unknown';

    /**
     * Sandbox not found state
     */
    public const NOT_FOUND = 'NotFound';

    /**
     * Get all valid statuses
     */
    public static function getAllStatuses(): array
    {
        return [
            self::PENDING,
            self::RUNNING,
            self::EXITED,
            self::UNKNOWN,
            self::NOT_FOUND,
        ];
    }

    /**
     * Check whether a status is valid.
     */
    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::getAllStatuses(), true);
    }

    /**
     * Check whether the sandbox is available (running).
     */
    public static function isAvailable(string $status): bool
    {
        return $status === self::RUNNING;
    }
}
