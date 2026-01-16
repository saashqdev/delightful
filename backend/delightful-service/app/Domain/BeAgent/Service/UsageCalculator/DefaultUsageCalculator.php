<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\BeAgent\Service\UsageCalculator;

/**
 * Default usage calculator implementation.
 * Returns empty array as default behavior.
 */
class DefaultUsageCalculator implements UsageCalculatorInterface
{
    /**
     * Calculate usage information for a task.
     * Default implementation returns empty array.
     *
     * @param int $taskId Task ID
     * @return array Empty array
     */
    public function calculateUsage(int $taskId): array
    {
        return [];
    }
}
