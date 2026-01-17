<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject;

/**
 * Memory operation action enum.
 */
enum MemoryOperationAction: string
{
    case ACCEPT = 'accept';   // Accept memory suggestion
    case REJECT = 'reject';   // Reject memory suggestion

    /**
     * Get operation description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ACCEPT => 'Accept',
            self::REJECT => 'Reject',
        };
    }

    /**
     * Get all operation values.
     */
    public static function getAllValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if operation is valid.
     */
    public static function isValid(string $action): bool
    {
        return in_array($action, self::getAllValues(), true);
    }
}
