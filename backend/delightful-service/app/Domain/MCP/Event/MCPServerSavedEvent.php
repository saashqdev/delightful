<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\MCP\Event;

use App\Domain\MCP\Entity\MCPServerEntity;

class MCPServerSavedEvent
{
    public function __construct(public MCPServerEntity $MCPServerEntity, public bool $create)
    {
    }
}
