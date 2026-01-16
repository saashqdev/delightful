<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Interfaces\SuperAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

/**
 * 创建文件版本响应 DTO.
 */
class CreateFileVersionResponseDTO extends AbstractDTO
{
    /**
     * 创建空响应实例.
     */
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * 转换为数组.
     */
    public function toArray(): array
    {
        return [];
    }
}
