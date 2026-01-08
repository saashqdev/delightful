<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use function Hyperf\Support\env;

return [
    'volcengine' => [
        'app_id' => env('ASR_VKE_APP_ID', ''),
        'token' => env('ASR_VKE_TOKEN', ''),
        'cluster' => env('ASR_VKE_CLUSTER', ''),
        'hot_words' => json_decode(env('ASR_VKE_HOTWORDS_CONFIG') ?? '[]', true) ?: [],
        'replacement_words' => json_decode(env('ASR_VKE_REPLACEMENT_WORDS_CONFIG') ?? '[]', true) ?: [],

        // ASR request configuration parameters @see https://www.volcengine.com/docs/6561/1354868
        'request_config' => [
            // Model name, currently only bigmodel
            'model_name' => 'bigmodel',
            // Model version number, 400 is the latest
            'model_version' => '400',
            // Inverse Text Normalization (ITN) is part of Automatic Speech Recognition (ASR) post-processing pipeline
            // ITN's task is to convert raw speech output from ASR model into written form to improve text readability
            // For example, "nineteen seventy" -> "1970" and "one hundred twenty-three dollars" -> "$123"
            'enable_itn' => true,
            // Enable punctuation recognition
            'enable_punc' => true,
            // Semantic smoothing: improve text readability and fluency of ASR results
            // By removing or modifying disfluent parts in ASR results, such as pause words, filler words, semantic repetitions, etc.
            'enable_ddc' => true,
            // When enabled, can return speaker information, works well within 10 people
            'enable_speaker_info' => true,
        ],
    ],
    'text_replacer' => [ // Currently Volcengine model only supports hot words, not replacement, for backup in extreme cases
        'fuzz' => [
            'replacement' => [
            ],
            'threshold' => 70,
        ],
    ],
];
