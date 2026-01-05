<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\MCP;

use JsonSerializable;

class Capabilities implements JsonSerializable
{
    public function __construct(
        protected ?bool $hasTools = null,
        protected ?bool $hasResources = null,
        protected ?bool $hasPrompts = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        $capabilities = [
        ];
        if ($this->hasTools) {
            $capabilities['tools'] = [
                'listChanged' => false,
            ];
        }
        return $capabilities;
    }
}
