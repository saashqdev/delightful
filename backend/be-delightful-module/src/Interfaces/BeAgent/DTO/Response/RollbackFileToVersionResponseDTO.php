<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

/**
 * 文件回滚到指定版本响应DTO.
 */
class RollbackFileToVersionResponseDTO extends AbstractDTO
{
    public static function createEmpty(): self
    {
        return new self();
    }

    public function toArray(): array
    {
        return [];
    }
}
