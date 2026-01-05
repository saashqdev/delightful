<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Contract\Model;

class Rerank
{
    public function __construct(public array $result)
    {
    }

    public function getResults(): array
    {
        return $this->result;
    }
}
