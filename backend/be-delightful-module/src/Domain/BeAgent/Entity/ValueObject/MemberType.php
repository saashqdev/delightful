<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject;

use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Delightful\BeDelightful\ErrorCode\BeAgentErrorCode;

/**
 * Member type value object
 *
 * Encapsulates business logic and validation rules for member type
 */
enum MemberType: string
{
    case USER = 'User';
    case DEPARTMENT = 'Department';

    /**
     * Create instance from string.
     */
    public static function fromString(string $type): self
    {
        return match ($type) {
            'User' => self::USER,
            'Department' => self::DEPARTMENT,
            default => ExceptionBuilder::throw(BeAgentErrorCode::INVALID_MEMBER_TYPE)
        };
    }

    /**
     * Create instance from value.
     */
    public static function fromValue(string $value): self
    {
        return self::from($value);
    }

    /**
     * Get value
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Whether is user type.
     */
    public function isUser(): bool
    {
        return $this === self::USER;
    }

    /**
     * Whether is department type.
     */
    public function isDepartment(): bool
    {
        return $this === self::DEPARTMENT;
    }

    /**
     * Get description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::USER => 'User',
            self::DEPARTMENT => 'Department',
        };
    }
}
