<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Comment\constant;

/**
 * attachmentcomesource.
 */
class AttachmentTargetType
{
    /**
     * 未知.
     */
    public const NONE = 0;

    /**
     * 待办.
     */
    public const TODO = 1;

    /**
     * comment.
     */
    public const COMMENT = 2;
}
