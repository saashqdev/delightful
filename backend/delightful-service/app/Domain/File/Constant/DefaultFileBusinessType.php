<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\File\Constant;

enum DefaultFileBusinessType: string
{
    case SERVICE_PROVIDER = 'service_provider';
    case FLOW = 'flow';
    case MAGIC = 'magic';
    case MODE = 'mode';
}
