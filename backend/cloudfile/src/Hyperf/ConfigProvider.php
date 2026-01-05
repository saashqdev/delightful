<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Hyperf;

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
                    'description' => 'The config of cloud-file-sdk.',
                    'source' => __DIR__ . '/publish/cloudfile.php',
                    'destination' => BASE_PATH . '/config/autoload/cloudfile.php',
                ],
            ],
        ];
    }
}
