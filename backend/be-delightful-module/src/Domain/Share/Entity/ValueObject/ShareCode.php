<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Share\Entity\ValueObject;

use InvalidArgumentException;

/**
 * Share code value object
 * Represents a unique share identifier.
 */
class ShareCode
{
    /**
     * Minimum length of share code.
     */
    private const int MIN_LENGTH = 6;

    /**
     * Maximum length of share code.
     */
    private const int MAX_LENGTH = 16;

    /**
     * Share code value
     */
    private string $value;

    /**
     * Constructor.
     *
     * @param string $value Share code value
     * @throws InvalidArgumentException Thrown when share code is invalid
     */
    public function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    /**
     * Convert to string.
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Create a new share code instance.
     *
     * @param string $value Share code value
     */
    public static function create(string $value): self
    {
        return new self($value);
    }

    /**
     * Get share code value
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Check if equals to another share code
     *
     * @param ShareCode $other Another share code
     */
    public function equals(ShareCode $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Validate share code
     *
     * @param string $value Share code value
     * @throws InvalidArgumentException Thrown when share code is invalid
     */
    private function validate(string $value): void
    {
        // Check length
        $length = mb_strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf('Share code length must be between %d and %d characters', self::MIN_LENGTH, self::MAX_LENGTH)
            );
        }

        // Check format (only allow letters, numbers and some special characters)
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
            throw new InvalidArgumentException('Share code can only contain letters, numbers, underscores and hyphens');
        }
    }
}
