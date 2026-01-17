<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Chat\DTO\Message\ChatMessage\Item\ValueObject;

/**
 * Memory operation scenario enum.
 */
enum MemoryOperationScenario: string
{
    case ADMIN_PANEL = 'admin_panel';           // Admin panel
    case MEMORY_CARD_QUICK = 'memory_card_quick'; // Memory card quick operation

    /**
     * Get scenario description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::ADMIN_PANEL => 'Admin Panel',
            self::MEMORY_CARD_QUICK => 'Memory Card Quick Operation',
        };
    }

    /**
     * Get all scenario values.
     */
    public static function getAllValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if scenario is valid.
     */
    public static function isValid(string $scenario): bool
    {
        return in_array($scenario, self::getAllValues(), true);
    }

    /**
     * Get default scenario.
     */
    public static function getDefault(): self
    {
        return self::ADMIN_PANEL;
    }
}
