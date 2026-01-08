<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Domain\Chat\Entity\ValueObject\LLMModelEnum;
use Hyperf\Codec\Json;

use function Hyperf\Support\env;

return [
    // RPM configuration
    'rpm_config' => [
        // Organization RPM rate limiting
        'organization' => 1000,
        // User rate limiting
        'user' => 100,
        // App rate limiting
        'app' => 100,
    ],
    // Default quota configuration
    'default_amount_config' => [
        // Organization default quota
        'organization' => 500000,
        // Personal default quota
        'user' => 1000,
    ],
    // Global model fallback chain
    'model_fallback_chain' => [
        // Chat model
        'chat' => Json::decode(env('CHAT_MODEL_FALLBACK_CHAIN', '{}')) ?: [LLMModelEnum::GPT_41->value, LLMModelEnum::GPT_4O->value, LLMModelEnum::DEEPSEEK_V3->value],
        // Embedding
        'embedding' => [],
    ],
    // Proxy configuration for accessing foreign services
    'http' => [
        'proxy' => env('HTTP_PROXY'),
    ],
];
