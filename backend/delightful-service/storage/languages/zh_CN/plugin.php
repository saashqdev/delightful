<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'param_error' => 'Parameter error',
    'not_found' => 'Plugin not found',
    'name' => [
        'required' => 'Plugin name is required',
    ],
    'description' => [
        'required' => 'Plugin description is required',
    ],
    'type' => [
        'required' => 'Plugin type is required',
        'modification_not_allowed' => 'Plugin type modification not allowed',
    ],
    'creator' => [
        'required' => 'Creator is required',
    ],
    'api_config' => [
        'required' => 'API configuration is required',
        'api_url' => [
            'required' => 'API address is required',
            'invalid' => 'API address invalid',
        ],
        'auth_type' => [
            'required' => 'Authentication type is required',
        ],
        'auth_config' => [
            'invalid' => 'Authentication configuration invalid',
        ],
    ],
];
