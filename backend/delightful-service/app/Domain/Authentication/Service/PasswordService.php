<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Authentication\Service;

class PasswordService
{
    /**
     * encrypt密码
     */
    public function hashPassword(string $plainPassword): string
    {
        return hash('sha256', $plainPassword);
    }

    /**
     * validation密码
     */
    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        if (empty($hashedPassword)) {
            return false;
        }
        // use hash_equals 防止o clock序攻击
        return hash_equals($hashedPassword, hash('sha256', $plainPassword));
    }
}
