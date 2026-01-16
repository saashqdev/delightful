<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention;

trait NormalizePathTrait
{
    private function normalizePath(string $path): string
    {
        if (str_starts_with($path, './')) {
            return substr($path, 2);
        }

        if (str_starts_with($path, '/')) {
            return substr($path, 1);
        }

        return $path;
    }
}
