<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
return [
    'volcengine' => [
        'invalid_response_format' => 'Volcengine response format invalid',
        'submit_failed' => 'Failed to call Volcengine API to submit task',
        'submit_exception' => 'Exception occurred when calling Volcengine to submit task',
        'query_failed' => 'Failed to call Volcengine API to query result',
        'query_exception' => 'Exception occurred when calling Volcengine to query result',
        'config_incomplete' => 'Volcengine configuration incomplete, missing app_id, token or cluster',
        'task_id_required' => 'Task ID cannot be empty',
        'bigmodel' => [
            'invalid_response_format' => 'Big model ASR response format invalid',
            'submit_exception' => 'Exception occurred when calling big model ASR to submit task',
            'query_exception' => 'Exception occurred when calling big model ASR to query result',
        ],
    ],
];
