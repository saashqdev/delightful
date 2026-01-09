<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 资source列表方法处理器.
 */
class ResourceListHandler extends AbstractMethodHandler
{
    /**
     * 处理资source列表请求.
     */
    public function handle(MessageInterface $request): ?array
    {
        return [
            'resources' => $this->getResourceManager()->getResources(),
        ];
    }
}
