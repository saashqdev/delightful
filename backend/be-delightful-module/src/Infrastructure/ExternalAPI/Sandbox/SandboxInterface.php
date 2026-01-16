<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\BeDelightful\Infrastructure\ExternalAPI\Sandbox;

interface SandboxInterface
{
    /**
     * 创建沙箱实例.
     */
    public function create(SandboxStruct $struct): SandboxResult;

    /**
     * 获取沙箱状态.
     */
    public function getStatus(string $sandboxId): SandboxResult;

    /**
     * 销毁沙箱实例.
     */
    public function destroy(string $sandboxId): SandboxResult;

    /**
     * 获取沙箱WebSocket连接地址.
     */
    public function getWebsocketUrl(string $sandboxId): string;
}
