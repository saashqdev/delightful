<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Authentication\Entity\ValueObject;

enum ApiKeyProviderType: int
{
    case None = 0;
    case Flow = 1;
    case MCP = 2;
}
