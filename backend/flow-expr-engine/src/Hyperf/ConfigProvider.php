<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Hyperf;

use Delightful\FlowExprEngine\Hyperf\Listener\BootSdkListener;
use Psr\Http\Client\ClientInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ClientInterface::class => SimpleClientFactory::class,
            ],
            'commands' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'listeners' => [
                // Business logic can register here manually, automatic registration not provided
                BootSdkListener::class => 1000,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for delightful/flow-expr-engine.',
                    'source' => __DIR__ . '/publish/flow_expr_engine.php',
                    'destination' => BASE_PATH . '/config/autoload/flow_expr_engine.php',
                ],
            ],
        ];
    }
}
