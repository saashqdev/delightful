<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Entity\ValueObject;

enum SourceType: int
{
    /**
     * outside部file.
     */
    case EXTERNAL_FILE = 1;
}
