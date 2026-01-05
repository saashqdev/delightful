<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\AsyncEvent;

use Delightful\AsyncEvent\Kernel\Crontab\ClearHistoryCrontab;
use Delightful\AsyncEvent\Kernel\Crontab\RetryCrontab;
use Hyperf\Crontab\Crontab;
use Hyperf\Di\Definition\PriorityDefinition;
use Psr\EventDispatcher\EventDispatcherInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                EventDispatcherInterface::class => new PriorityDefinition(EventDispatcherFactory::class, 1),
            ],
            'commands' => [
            ],
            'crontab' => [
                // Since hyperf3 deprecated @ annotations and php7 doesn't support native annotations, configuration approach is used here
                'crontab' => [
                    (new Crontab())
                        ->setName('AsyncEventClearHistory')
                        ->setRule('*/5 5 * * *')
                        ->setCallback([ClearHistoryCrontab::class, 'execute'])
                        // Unable to use config function here yet, using env temporarily
                        ->setEnable((bool) \Hyperf\Support\env('ASYNC_EVENT_CLEAR_HISTORY', true))
                        ->setSingleton(true)
                        ->setMemo('Clear historical records'),
                    (new Crontab())
                        ->setName('AsyncEventRetry')
                        ->setRule('*/10 * * * * *')
                        ->setCallback([RetryCrontab::class, 'execute'])
                        ->setEnable(true)
                        ->setSingleton(true)
                        ->setMemo('Event retry'),
                ],
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'config file.',
                    'source' => __DIR__ . '/../publish/async_event.php',  // Corresponding config file path
                    'destination' => BASE_PATH . '/config/autoload/async_event.php', // Copy to this file path
                ],
                [
                    'id' => 'migration',
                    'description' => 'migration file.',
                    'source' => __DIR__ . '/../publish/migrations/2023_05_18_104130_create_async_event_records.php',  // Corresponding config file path
                    'destination' => BASE_PATH . '/migrations/2023_05_18_104130_create_async_event_records.php', // Copy to this file path
                ],
            ],
        ];
    }
}
