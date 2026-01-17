<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\Utils;

/**
 * Password hashing utility.
 */
class PasswordHasher
{
    protected string $hashAlgo = PASSWORD_BCRYPT;

    /**
     * Password hashing options.
     */
    protected array $hashOptions = [
        'cost' => 10,
    ];

    /**
     * Hash a password.
     *
     * @param string $password Original password
     * @return string Hashed password
     */
    public function hash(string $password): string
    {
        return password_hash($password, $this->hashAlgo, $this->hashOptions);
    }

    /**
     * Verify whether the password is correct.
     *
     * @param string $password Original password
     * @param string $hash Hashed password
     * @return bool Whether verification passed
     */
    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check whether the password hash needs to be rehashed.
     *
     * @param string $hash Hashed password
     * @return bool Whether rehashing is needed
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->hashAlgo, $this->hashOptions);
    }
}
