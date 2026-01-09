<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Infrastructure\Core\PHPSandbox\ExecutableCode\Methods\LogicalIfMethod;
use Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\Date\GetISO8601Date;
use Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\Date\GetISO8601DateTime;
use Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\Date\GetISO8601DateTimeWithOffset;
use Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods\Date\GetRFC1123DateTime;

return [
    'logger' => [
        'enabled' => false,
    ],
    // PHP functions allowed when executing code with php_sandbox
    'php_sandbox_functions' => [
        [
            'group' => 'string',
            'functions' => [
                'str_contains', 'str_replace', 'mb_strlen', 'mb_str_pad', 'explode',
                'preg_replace', 'preg_split', 'str_repeat', 'str_split',
                'strpos', 'strlen', 'substr', 'ltrim', 'rtrim', 'trim',
                'strtolower', 'strtoupper', 'str_starts_with', 'str_ends_with', 'str_pad',
                'sprintf', 'uniqid', 'htmlspecialchars', 'htmlentities', 'strip_tags', 'nl2br', 'wordwrap',
                'addslashes', 'stripslashes', 'substr_replace', 'strtr', 'str_shuffle', 'chunk_split',
            ],
        ],
        [
            'group' => 'array',
            'functions' => [
                'array_count_values', 'array_fill', 'array_fill_keys', 'array_filter', 'array_map', 'array_reduce',
                'array_replace', 'array_replace_recursive', 'end', 'current', 'key', 'list', 'array_key_exists', 'array_keys',
                'array_change_key_case', 'array_chunk', 'array_combine', 'array_slice',
                'array_column', 'array_values', 'array_merge', 'array_diff', 'array_intersect', 'array_unique',
                'array_search', 'array_flip', 'array_reverse', 'array_splice', 'in_array', 'shuffle',
                'array_walk', 'array_walk_recursive', 'array_push', 'array_pop', 'array_shift', 'array_unshift',
            ],
        ],
        [
            'group' => 'math operations',
            'functions' => [
                'abs', 'ceil', 'floor', 'round', 'sqrt', 'pow', 'exp', 'log', 'log10', 'sin', 'cos', 'tan', 'asin', 'acos',
                'atan', 'atan2', 'pi', 'fmod', 'rand', 'mt_rand', 'mt_srand', 'random_int', 'random_bytes', 'min', 'max', 'intdiv',
                'bcadd', 'bcsub', 'bcmul', 'bcdiv', 'bcpow', 'bcsqrt', 'bcmod',
            ],
        ],
        [
            'group' => 'serialization',
            'functions' => [
                'json_encode', 'json_decode', 'serialize', 'unserialize',
            ],
        ],
        [
            'group' => 'encryption',
            'functions' => ['md5', 'sha1', 'hash', 'password_hash', 'password_verify', 'password_needs_rehash', 'hash_hmac'],
        ],
        [
            'group' => 'date/time',
            'functions' => ['date', 'time', 'strtotime', 'microtime', 'gmdate', 'idate', 'getdate', 'date_default_timezone_set', 'date_default_timezone_get',
                'mktime', 'localtime', 'checkdate', GetISO8601Date::class, GetISO8601DateTime::class, GetISO8601DateTimeWithOffset::class, GetRFC1123DateTime::class,
            ],
        ],
        [
            'group' => 'type checking',
            'functions' => [
                'is_array', 'is_numeric', 'is_string', 'is_int', 'is_float', 'is_bool', 'is_object', 'is_null', 'gettype',
            ],
        ],
        [
            'group' => 'URL operations',
            'functions' => [
                'parse_url', 'http_build_query', 'parse_str', 'urlencode', 'urldecode', 'rawurlencode', 'rawurldecode',
            ],
        ],
        [
            'group' => 'logic',
            'functions' => [
                LogicalIfMethod::class,
            ],
        ],
        [
            // Hidden, but can be used
            'group' => 'hide',
            'functions' => [
                'var_dump', 'print_r', 'print', 'printf', 'json_last_error', 'json_last_error_msg',
            ],
        ],
    ],
    'php_sandbox_constants' => [
        'PHP_EOL', 'JSON_UNESCAPED_UNICODE', 'JSON_PRETTY_PRINT', 'JSON_ERROR_NONE',
    ],
];
