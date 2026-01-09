<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\File\Constant;

enum DefaultFileType: int
{
    case DEFAULT = 0; // 默认的file
    case NOT_DEFAULT = 1; // organizationupload的
}
