<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\TempAuth;

interface TempAuthInterface
{
    public function create(array $info, int $ttl = 60): string;

    public function has(string $code): bool;

    public function get(string $code): array;

    public function delete(string $code): void;

    public function is(string $code): bool;
}
