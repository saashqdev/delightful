<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\Common\MessageExtra\BeAgent\Mention;

use App\Infrastructure\Core\AbstractDTO;

/**
 * Base class for mention data.
 */
abstract class MentionData extends AbstractDTO
{
    /**
     * Get the data type.
     */
    abstract public function getDataType(): string;
}
