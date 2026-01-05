<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Entity\ValueObject;

enum NaturalLanguageProcessing: string
{
    case DEFAULT = 'default';
    case EMBEDDING = 'embedding'; // 嵌入
    case LLM = 'llm'; // 大语言
}
