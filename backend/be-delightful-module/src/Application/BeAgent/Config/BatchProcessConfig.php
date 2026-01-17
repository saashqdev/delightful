<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Application\SuperAgent\Config;

/**
 * Batch Process Configuration.
 * Batch processing configuration class - centrally manages various parameters for batch file saving.
 */
class BatchProcessConfig
{
    /**
     * Concurrency control configuration.
     */
    public const int DEFAULT_MAX_CONCURRENCY = 5;   // Default maximum concurrency (considering file IO and memory usage)

    public const int MIN_CONCURRENCY = 1;           // Minimum concurrency

    public const int MAX_CONCURRENCY = 8;           // Maximum concurrency limit (file processing should not be too high)

    /**
     * Batch size limit.
     */
    public const int DEFAULT_BATCH_SIZE_LIMIT = 50; // Default batch size limit

    public const int MAX_BATCH_SIZE_LIMIT = 100;    // Maximum batch size limit

    /**
     * Feature switches.
     */
    public const bool ENABLE_PERFORMANCE_MONITORING = true; // Performance monitoring switch

    public const bool ENABLE_DETAILED_LOGGING = true;       // Detailed logging switch

    /**
     * Get maximum concurrency.
     * File processing is an IO-intensive operation, requiring consideration of memory usage and system resource limits.
     *
     * @param int $fileCount File count
     * @return int Actual concurrency
     */
    public static function getMaxConcurrency(int $fileCount): int
    {
        // File processing concurrency strategy:
        // 1. Considering each file is up to 10MB, too much concurrency will consume a lot of memory
        // 2. File upload involves temporary files and network IO, which consumes significant resources
        // 3. Distributed locks and database connections also have limitations

        if ($fileCount == 1) {
            return 1;
        }

        if ($fileCount <= 3) {
            return 3;  // Use 3 concurrent for small number of files
        }

        // Maximum concurrency does not exceed 5 regardless of file count
        return self::DEFAULT_MAX_CONCURRENCY;
    }

    /**
     * Whether to enable concurrent processing.
     *
     * @param int $fileCount File count
     * @return bool Whether to enable concurrency
     */
    public static function shouldEnableConcurrency(int $fileCount): bool
    {
        // Do not use concurrency when file count is less than 2
        return $fileCount >= 2;
    }

    /**
     * Get batch size limit.
     *
     * @return int Batch size limit
     */
    public static function getBatchSizeLimit(): int
    {
        return self::DEFAULT_BATCH_SIZE_LIMIT;
    }

    /**
     * Whether performance monitoring is enabled.
     *
     * @return bool Whether performance monitoring is enabled
     */
    public static function isPerformanceMonitoringEnabled(): bool
    {
        return self::ENABLE_PERFORMANCE_MONITORING;
    }

    /**
     * Whether detailed logging is enabled.
     *
     * @return bool Whether detailed logging is enabled
     */
    public static function isDetailedLoggingEnabled(): bool
    {
        return self::ENABLE_DETAILED_LOGGING;
    }
}
