<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\SandboxOS\Agent\Constant;

/**
 * Workspace status constants
 * Define various status values of Agent workspace.
 */
class WorkspaceStatus
{
    /**
     * Uninitialized - AgentDispatcher not created or not initialized.
     */
    public const int UNINITIALIZED = 0;

    /**
     * Initializing - Reserved status, not used yet.
     */
    public const int INITIALIZING = 1;

    /**
     * Initialization complete - workspace fully available.
     */
    public const int READY = 2;

    /**
     * Initialization error - exception occurred during initialization.
     */
    public const int ERROR = -1;

    /**
     * Get status description.
     *
     * @param int $status Status value
     * @return string Status description
     */
    public static function getDescription(int $status): string
    {
        return match ($status) {
            self::UNINITIALIZED => 'Uninitialized',
            self::INITIALIZING => 'Initializing',
            self::READY => 'Ready',
            self::ERROR => 'Error',
            default => 'Unknown status',
        };
    }

    /**
     * Check if status is ready state.
     *
     * @param int $status Status value
     * @return bool Whether ready
     */
    public static function isReady(int $status): bool
    {
        return $status === self::READY;
    }

    /**
     * Check if status is error state.
     *
     * @param int $status Status value
     * @return bool Whether error
     */
    public static function isError(int $status): bool
    {
        return $status === self::ERROR;
    }
}
