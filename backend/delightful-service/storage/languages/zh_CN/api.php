<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    // Token related errors
    'token' => [
        'not_exist' => 'API token does not exist',
        'disabled' => 'API token is disabled',
        'expired' => 'API token has expired',
        'model_not_support' => 'Token does not support this model',
        'organization_not_support' => 'Token does not support this organization',
        'ip_not_in_white_list' => 'IP address not in whitelist',
        'quota_not_enough' => 'Token quota insufficient',
        'calculate_error' => 'Token calculation error',
        'create_error' => 'Failed to create token',
    ],

    // Model related errors
    'model' => [
        'not_support' => 'Model not supported',
        'response_fail' => 'Model response failed',
    ],

    // Organization related errors
    'organization' => [
        'quota_not_enough' => 'Organization quota insufficient',
    ],

    // Message related errors
    'message' => [
        'empty' => 'Message cannot be empty',
    ],

    // User related errors
    'user' => [
        'create_access_token_limit' => 'User exceeded access token creation limit',
        'use_access_token_limit' => 'User exceeded access token usage limit',
        'create_access_token_rate_limit' => 'User access token creation rate limited',
    ],

    // General errors
    'rate_limit' => 'Request rate limit exceeded',
    'msg_empty' => 'Message is empty',
    'user_id_not_exist' => 'User ID does not exist',
    'validate_failed' => 'Validation failed',
];
