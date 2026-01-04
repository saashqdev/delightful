<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */
use function Hyperf\Support\env;

return [
    // AI capability list configuration
    'abilities' => [
        'ai_ability_aes_key' => env('AI_ABILITY_CONFIG_AES_KEY', ''),
        // OCR recognition
        'ocr' => [
            'code' => 'ocr',
            'name' => [
                'zh_CN' => 'OCR Recognition',
                'en_US' => 'OCR Recognition',
            ],
            'description' => [
                'zh_CN' => 'This capability covers all OCR application scenarios on the platform, accurately capturing and extracting text information from PDFs, scanned documents, and various images.',
                'en_US' => 'This capability covers all OCR application scenarios on the platform, accurately capturing and extracting text information from PDFs, scanned documents, and various images.',
            ],
            'icon' => 'ocr-icon',
            'sort_order' => 1,
            'status' => env('AI_ABILITY_OCR_STATUS', true),
            'config' => [
                'url' => env('AI_ABILITY_OCR_URL', ''),
                'provider_code' => env('AI_ABILITY_OCR_PROVIDER', 'Official'),
                'api_key' => env('AI_ABILITY_OCR_API_KEY', ''),
            ],
        ],

        // Web search
        'web_search' => [
            'code' => 'web_search',
            'name' => [
                'zh_CN' => 'Web Search',
                'en_US' => 'Web Search',
            ],
            'description' => [
                'zh_CN' => 'This capability covers web search scenarios for AI models on the platform, accurately obtaining and integrating the latest news, facts and data information.',
                'en_US' => 'This capability covers web search scenarios for AI models on the platform, accurately obtaining and integrating the latest news, facts and data information.',
            ],
            'icon' => 'web-search-icon',
            'sort_order' => 2,
            'status' => env('AI_ABILITY_WEB_SEARCH_STATUS', true),
            'config' => [
                'url' => env('AI_ABILITY_WEB_SEARCH_URL', ''),
                'provider_code' => env('AI_ABILITY_WEB_SEARCH_PROVIDER', 'Official'),
                'api_key' => env('AI_ABILITY_WEB_SEARCH_API_KEY', ''),
            ],
        ],

        // Realtime speech recognition
        'realtime_speech_recognition' => [
            'code' => 'realtime_speech_recognition',
            'name' => [
                'zh_CN' => 'Realtime Speech Recognition',
                'en_US' => 'Realtime Speech Recognition',
            ],
            'description' => [
                'zh_CN' => 'This capability covers all speech-to-text application scenarios on the platform, monitoring audio streams in real-time and gradually outputting accurate text content.',
                'en_US' => 'This capability covers all speech-to-text application scenarios on the platform, monitoring audio streams in real-time and gradually outputting accurate text content.',
            ],
            'icon' => 'realtime-speech-icon',
            'sort_order' => 3,
            'status' => env('AI_ABILITY_REALTIME_SPEECH_STATUS', true),
            'config' => [
                'url' => env('AI_ABILITY_REALTIME_SPEECH_URL', ''),
                'provider_code' => env('AI_ABILITY_REALTIME_SPEECH_PROVIDER', 'Official'),
                'api_key' => env('AI_ABILITY_REALTIME_SPEECH_API_KEY', ''),
            ],
        ],

        // Audio file recognition
        'audio_file_recognition' => [
            'code' => 'audio_file_recognition',
            'name' => [
                'zh_CN' => 'Audio File Recognition',
                'en_US' => 'Audio File Recognition',
            ],
            'description' => [
                'zh_CN' => 'This capability covers all audio file-to-text application scenarios on the platform, accurately identifying speakers, audio text and other information.',
                'en_US' => 'This capability covers all audio file-to-text application scenarios on the platform, accurately identifying speakers, audio text and other information.',
            ],
            'icon' => 'audio-file-icon',
            'sort_order' => 4,
            'status' => env('AI_ABILITY_AUDIO_FILE_STATUS', true),
            'config' => [
                'url' => env('AI_ABILITY_AUDIO_FILE_URL', ''),
                'provider_code' => env('AI_ABILITY_AUDIO_FILE_PROVIDER', 'Official'),
                'api_key' => env('AI_ABILITY_AUDIO_FILE_API_KEY', ''),
            ],
        ],

        // Auto completion
        'auto_completion' => [
            'code' => 'auto_completion',
            'name' => [
                'zh_CN' => 'Auto Completion',
                'en_US' => 'Auto Completion',
            ],
            'description' => [
                'zh_CN' => 'This capability covers all input auto-completion scenarios on the platform, automatically completing content for users based on context understanding, allowing users to choose whether to accept.',
                'en_US' => 'This capability covers all input auto-completion scenarios on the platform, automatically completing content for users based on context understanding, allowing users to choose whether to accept.',
            ],
            'icon' => 'auto-completion-icon',
            'sort_order' => 5,
            'status' => env('AI_ABILITY_AUTO_COMPLETION_STATUS', true),
            'config' => [
                'model_id' => env('AI_ABILITY_AUTO_COMPLETION_MODEL_ID', null), // Corresponds to service_provider_models.model_id
            ],
        ],

        // Content summary
        'content_summary' => [
            'code' => 'content_summary',
            'name' => [
                'zh_CN' => 'Content Summary',
                'en_US' => 'Content Summary',
            ],
            'description' => [
                'zh_CN' => 'This capability covers all content summarization scenarios on the platform, performing in-depth analysis of long documents, reports or web articles.',
                'en_US' => 'This capability covers all content summarization scenarios on the platform, performing in-depth analysis of long documents, reports or web articles.',
            ],
            'icon' => 'content-summary-icon',
            'sort_order' => 6,
            'status' => env('AI_ABILITY_CONTENT_SUMMARY_STATUS', true),
            'config' => [
                'model_id' => env('AI_ABILITY_CONTENT_SUMMARY_MODEL_ID', null), // Corresponds to service_provider_models.model_id
            ],
        ],

        // Visual understanding
        'visual_understanding' => [
            'code' => 'visual_understanding',
            'name' => [
                'zh_CN' => 'Visual Understanding',
                'en_US' => 'Visual Understanding',
            ],
            'description' => [
                'zh_CN' => 'This capability covers all application scenarios that require AI models to perform visual understanding on the platform, accurately understanding content and complex relationships in various images.',
                'en_US' => 'This capability covers all application scenarios that require AI models to perform visual understanding on the platform, accurately understanding content and complex relationships in various images.',
            ],
            'icon' => 'visual-understanding-icon',
            'sort_order' => 7,
            'status' => env('AI_ABILITY_VISUAL_UNDERSTANDING_STATUS', true),
            'config' => [
                'model_id' => env('AI_ABILITY_VISUAL_UNDERSTANDING_MODEL_ID', null), // Corresponds to service_provider_models.model_id
            ],
        ],

        // Smart rename
        'smart_rename' => [
            'code' => 'smart_rename',
            'name' => [
                'zh_CN' => 'Smart Rename',
                'en_US' => 'Smart Rename',
            ],
            'description' => [
                'zh_CN' => 'This capability covers all AI renaming scenarios on the platform, automatically naming content titles for users based on context understanding.',
                'en_US' => 'This capability covers all AI renaming scenarios on the platform, automatically naming content titles for users based on context understanding.',
            ],
            'icon' => 'smart-rename-icon',
            'sort_order' => 8,
            'status' => env('AI_ABILITY_SMART_RENAME_STATUS', true),
            'config' => [
                'model_id' => env('AI_ABILITY_SMART_RENAME_MODEL_ID', null), // Corresponds to service_provider_models.model_id
            ],
        ],

        // AI optimization
        'ai_optimization' => [
            'code' => 'ai_optimization',
            'name' => [
                'zh_CN' => 'AI Optimization',
                'en_US' => 'AI Optimization',
            ],
            'description' => [
                'zh_CN' => 'This capability covers all AI content optimization scenarios on the platform, automatically optimizing content for users based on context understanding.',
                'en_US' => 'This capability covers all AI content optimization scenarios on the platform, automatically optimizing content for users based on context understanding.',
            ],
            'icon' => 'ai-optimization-icon',
            'sort_order' => 9,
            'status' => env('AI_ABILITY_AI_OPTIMIZATION_STATUS', true),
            'config' => [
                'model_id' => env('AI_ABILITY_AI_OPTIMIZATION_MODEL_ID', null), // Corresponds to service_provider_models.model_id
            ],
        ],
    ],
];
