<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use App\Domain\Chat\Entity\ValueObject\SearchEngineType;
use App\Infrastructure\ExternalAPI\Search\Adapter\BingSearchAdapter;
use App\Infrastructure\ExternalAPI\Search\Adapter\CloudswaySearchAdapter;
use App\Infrastructure\ExternalAPI\Search\Adapter\DuckDuckGoSearchAdapter;
use App\Infrastructure\ExternalAPI\Search\Adapter\GoogleSearchAdapter;
use App\Infrastructure\ExternalAPI\Search\Adapter\JinaSearchAdapter;
use App\Infrastructure\ExternalAPI\Search\Adapter\DelightfulSearchAdapter;
use App\Infrastructure\ExternalAPI\Search\Adapter\TavilySearchAdapter;

/*
 * Copyright (c) Be Delightful , Distributed under the MIT software license.
 */
use function Hyperf\Support\env;

return [
    'backend' => env('SEARCH_BACKEND', 'bing'),
    'drivers' => [
        SearchEngineType::Delightful->value => [
            'class_name' => DelightfulSearchAdapter::class,
            'base_url' => env('DELIGHTFUL_SEARCH_BASE_URL', ''),
            'api_key' => env('DELIGHTFUL_SEARCH_API_KEY', ''),
        ],
        SearchEngineType::Tavily->value => [
            'class_name' => TavilySearchAdapter::class,
            'api_key' => env('TAVILY_API_KEY', ''),
        ],
        SearchEngineType::Google->value => [
            'class_name' => GoogleSearchAdapter::class,
            // if你useGOOGLE，你need指定searchAPIkey。注意你还should在env中指定cx。
            'api_key' => env('GOOGLE_SEARCH_API_KEY', ''),
            // if你在usegoogle，请指定searchcx,也就是GOOGLE_SEARCH_ENGINE_ID
            'cx' => env('GOOGLE_SEARCH_CX', ''),
        ],
        SearchEngineType::Bing->value => [
            'class_name' => BingSearchAdapter::class,
            'endpoint' => env('BING_SEARCH_ENDPOINT', 'https://api.bing.microsoft.com/v7.0/search'),
            'api_key' => env('BING_SEARCH_API_KEY', ''),
            'mkt' => env('BING_SEARCH_MKT', 'zh-CN'),
        ],
        SearchEngineType::DuckDuckGo->value => [
            'class_name' => DuckDuckGoSearchAdapter::class,
            'region' => env('BING_SEARCH_MKT', 'cn-zh'),
        ],
        SearchEngineType::Jina->value => [
            'class_name' => JinaSearchAdapter::class,
            'api_key' => env('JINA_SEARCH_API_KEY', ''),
            'region' => env('JINA_SEARCH_REGION'),
        ],
        SearchEngineType::Cloudsway->value => [
            'class_name' => CloudswaySearchAdapter::class,
            'base_path' => env('CLOUDSWAY_BASE_PATH', ''),
            'endpoint' => env('CLOUDSWAY_ENDPOINT', ''),  // 从 console.cloudsway.ai get
            'access_key' => env('CLOUDSWAY_ACCESS_KEY', ''),  // 从 console.cloudsway.ai get
        ],
    ],
];
