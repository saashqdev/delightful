<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP\Types\Message;

interface MessageInterface
{
    public function getId(): int;

    public function getMethod(): string;

    public function getJsonRpc(): string;

    public function getParams(): ?array;
}
