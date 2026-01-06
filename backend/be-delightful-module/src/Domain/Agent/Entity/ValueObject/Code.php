<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperDelightful\Domain\Agent\Entity\ValueObject;

enum Code: string
{
    case SuperDelightfulAgent = 'SMA';

    public function gen(): string
    {
        return $this->value . '-' . str_replace('.', '-', uniqid('', true));
    }
}
