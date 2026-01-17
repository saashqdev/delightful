<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\ExternalAPI\Sandbox;

interface SandboxInterface
{
    /**
     * Create sandbox instance.
     */
    public function create(SandboxStruct $struct): SandboxResult;

    /**
     * Get sandbox status.
     */
    public function getStatus(string $sandboxId): SandboxResult;

    /**
     * Destroy sandbox instance.
     */
    public function destroy(string $sandboxId): SandboxResult;

    /**
     * Get sandbox WebSocket connection address.
     */
    public function getWebsocketUrl(string $sandboxId): string;
}
