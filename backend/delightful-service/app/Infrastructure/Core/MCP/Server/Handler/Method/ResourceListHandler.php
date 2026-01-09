<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 资source列tablemethodprocess器.
 */
class ResourceListHandler extends AbstractMethodHandler
{
    /**
     * process资source列tablerequest.
     */
    public function handle(MessageInterface $request): ?array
    {
        return [
            'resources' => $this->getResourceManager()->getResources(),
        ];
    }
}
