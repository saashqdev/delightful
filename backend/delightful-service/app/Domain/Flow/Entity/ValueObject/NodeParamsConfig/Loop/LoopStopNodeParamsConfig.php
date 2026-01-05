<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Loop;

use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\NodeParamsConfig;

class LoopStopNodeParamsConfig extends NodeParamsConfig
{
    public function validate(): array
    {
        return [];
    }

    public function generateTemplate(): void
    {
        $this->node->setMeta([
            'parent_id' => '',
        ]);
    }
}
