<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Contract\Flow\CodeExecutor;

enum CodeLanguage: string
{
    case None = 'none';
    case PHP = 'php';
    case PYTHON = 'python';

    public function isSupport(): bool
    {
        return in_array($this, [self::PHP, self::PYTHON]);
    }
}
