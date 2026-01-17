<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

/**
 * Create file version response DTO.
 */
class CreateFileVersionResponseDTO extends AbstractDTO
{
    /**
     * Create empty response instance.
     */
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [];
    }
}
