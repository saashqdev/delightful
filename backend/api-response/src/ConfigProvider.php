<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\ApiResponse;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
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
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for ApiResponse',
                    'source' => __DIR__ . '/../publish/api-response.php',
                    'destination' => BASE_PATH . '/config/autoload/api-response.php',
                ],
            ],
        ];
    }
}
