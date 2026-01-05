<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\SuperMagic\Domain\Agent\Entity\ValueObject;

enum Code: string
{
    case SuperMagicAgent = 'SMA';

    public function gen(): string
    {
        return $this->value . '-' . str_replace('.', '-', uniqid('', true));
    }
}
