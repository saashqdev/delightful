<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\DelightfulAIApi;

use BeDelightful\SdkBase\SdkBase;
use BeDelightful\SdkBase\SdkBaseContext;
use RuntimeException;

/**
 * @property Api\Chat $chat
 */
class DelightfulAIApi
{
    public const string NAME = 'delightful_ai';

    protected array $routes = [
        'chat' => Api\Chat::class,
    ];

    protected array $fetchedDefinitions = [];

    public function __construct(SdkBase $container)
    {
        if (! SdkBaseContext::has(self::NAME)) {
            SdkBaseContext::register(self::NAME, $container);
        }
        $this->register($container);
    }

    public function __get(string $name)
    {
        $api = $this->fetchedDefinitions[$name] ?? null;
        if (! $api) {
            throw new RuntimeException("no allowed route [{$name}]");
        }
        return $api;
    }

    protected function register(SdkBase $container): void
    {
        foreach ($this->routes as $key => $route) {
            $this->fetchedDefinitions[$key] = new $route($container);
        }
    }
}
