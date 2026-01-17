<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Constant;

use InvalidArgumentException;

/**
 * Convert task status enum.
 */
enum ConvertStatusEnum: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    /**
     * Get all valid status values.
     */
    public static function getValidStatuses(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Check if status is valid.
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::getValidStatuses(), true);
    }

    /**
     * Create enum instance from string.
     */
    public static function fromString(string $status): self
    {
        return match ($status) {
            'pending' => self::PENDING,
            'processing' => self::PROCESSING,
            'completed' => self::COMPLETED,
            'failed' => self::FAILED,
            default => throw new InvalidArgumentException("Invalid status: {$status}"),
        };
    }
}
