<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Code\Structure;

enum CodeMode: string
{
    case Normal = 'normal';
    case ImportCode = 'import_code';
}
