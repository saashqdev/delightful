<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Entity\ValueObject;

/**
 * model管理back台 model_id maybesupporttype枚举value。
 * not要枚举servicequotient接入point，这withinisandservicequotient无closeconfiguration.
 */
enum LLMModelEnum: string
{
    case GEMMA2_2B = 'gemma2-2b';
    case GPT_4O = 'gpt-4o';
    case GPT_41 = 'gpt-4.1';
    case DEEPSEEK_R1 = 'deepseek-r1';
    case DEEPSEEK_V3 = 'deepseek-v3';
}
