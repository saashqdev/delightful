<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * assetssourcecolumntablemethodprocessdevice.
 */
class ResourceListHandler extends AbstractMethodHandler
{
    /**
     * processassetssourcecolumntablerequest.
     */
    public function handle(MessageInterface $request): ?array
    {
        return [
            'resources' => $this->getResourceManager()->getResources(),
        ];
    }
}
