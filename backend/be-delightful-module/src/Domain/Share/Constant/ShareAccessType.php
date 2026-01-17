<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Share\Constant;

/**
 * Share access type enum.
 */
enum ShareAccessType: int
{
    case SelfOnly = 1;                // Only accessible by self
    case OrganizationInternal = 2;    // Accessible within organization
    case SpecificTarget = 3;          // Accessible by specific departments/members
    case Internet = 4;                // Accessible via internet (requires link)

    /**
     * Get the description of the share type.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::SelfOnly => 'Only accessible by self',
            self::OrganizationInternal => 'Accessible within organization',
            self::SpecificTarget => 'Accessible by specific departments/members',
            self::Internet => 'Accessible via internet',
        };
    }

    /**
     * Check if password protection is required.
     */
    public function needsPassword(): bool
    {
        return $this === self::Internet;
    }

    /**
     * Check if specific targets are required.
     */
    public function needsTargets(): bool
    {
        return $this === self::SpecificTarget;
    }
}
