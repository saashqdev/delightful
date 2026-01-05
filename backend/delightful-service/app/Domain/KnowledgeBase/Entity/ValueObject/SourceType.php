<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

enum SourceType: int
{
    /**
     * 外部文件.
     */
    case EXTERNAL_FILE = 1;
}
