<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 通知初始化方法处理器.
 */
class NotificationInitializedHandler extends AbstractMethodHandler
{
    /**
     * 处理通知初始化请求.
     * 不需要return数据.
     */
    public function handle(MessageInterface $request): ?array
    {
        return null;
    }
}
