<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * model管理后台 model_id 可能支持type的枚举value。
 * 不要枚举服务商的接入点，这里是与服务商无关的configuration.
 */
enum LLMModelEnum: string
{
    case GEMMA2_2B = 'gemma2-2b';
    case GPT_4O = 'gpt-4o';
    case GPT_41 = 'gpt-4.1';
    case DEEPSEEK_R1 = 'deepseek-r1';
    case DEEPSEEK_V3 = 'deepseek-v3';
}
