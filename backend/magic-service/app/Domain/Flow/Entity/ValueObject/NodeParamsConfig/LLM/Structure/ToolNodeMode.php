<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\LLM\Structure;

enum ToolNodeMode: string
{
    case PARAMETER = 'parameter';
    case LLM = 'llm';

    public function isLLM(): bool
    {
        return $this === self::LLM;
    }
}
