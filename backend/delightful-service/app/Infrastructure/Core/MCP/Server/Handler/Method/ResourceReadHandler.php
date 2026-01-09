<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Server\Handler\Method;

use App\Infrastructure\Core\MCP\Exception\InvalidParamsException;
use App\Infrastructure\Core\MCP\Types\Message\MessageInterface;

/**
 * 资源readmethodprocess器.
 */
class ResourceReadHandler extends AbstractMethodHandler
{
    /**
     * get该process器支持的methodname.
     */
    public function getMethod(): string
    {
        return 'resources/read';
    }

    /**
     * process资源readrequest.
     */
    public function handle(MessageInterface $request): ?array
    {
        $params = $request->getParams();
        if (! isset($params['id'])) {
            throw new InvalidParamsException('Resource ID is required');
        }

        $resource = $this->getResourceManager()->getResource($params['id']);
        if ($resource === null) {
            throw new InvalidParamsException("Resource '{$params['id']}' not found");
        }

        return [
            'resource' => $resource,
        ];
    }
}
