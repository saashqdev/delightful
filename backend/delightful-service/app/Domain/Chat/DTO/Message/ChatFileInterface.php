<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message;

/**
 * 从message中get文件ids，用于判断user是否有文件的upload/下载permission.
 */
interface ChatFileInterface extends MessageInterface
{
    /**
     * @return array<string>
     */
    public function getFileIds(): array;
}
