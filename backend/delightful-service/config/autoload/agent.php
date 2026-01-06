<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    // Temporarily pin the delightful-mind-search AI code; it returns hardcoded search results instead of running through flow logic.
    'deep_search' => [
        'ai_code' => env('AGGREGATE_SEARCH_AI_CODE', 'DELIGHTFUL-FLOW-672c6375371f51-29426462'),
    ],
    'ai_image' => [
        'ai_code' => env('AI_IMAGE_AI_CODE', 'DELIGHTFUL-FLOW-676523f6047b56-15495224'),
    ],
    'simple_search' => [
        'ai_code' => env('SIMPLE_SEARCH_AI_CODE', 'DELIGHTFUL-FLOW-67664dea979e49-60291002'),
    ],
    'default_conversation_ai_codes' => env('DEFAULT_CONVERSATION_AI_CODES'),
];
