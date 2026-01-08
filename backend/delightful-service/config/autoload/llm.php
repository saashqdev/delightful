<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    // Organization default quota
    'organization_default_amount' => 500000,
    // Organization RPM rate limiting
    'organization_rpm_limit' => 1000,
    // User rate limiting
    'user_rpm_limit' => 100,
    // App rate limiting
    'app_rpm_limit' => 100,
    // AWS Bedrock auto cache switch
    'aws_bedrock_auto_cache' => env('LLM_AWS_BEDROCK_AUTO_CACHE', true),
    // OpenAI compatible model auto cache switch
    'openai_auto_cache' => env('LLM_OPENAI_AUTO_CACHE', true),
];
