<?php

declare(strict_types=1);
/**
 * This file is part of Dtyq.
 */

namespace Delightful\CodeExecutor;

use Delightful\CodeExecutor\Executor\Aliyun\AliyunRuntimeClient;
use Delightful\CodeExecutor\Executor\Aliyun\AliyunRuntimeClientFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                AliyunRuntimeClient::class => AliyunRuntimeClientFactory::class,
            ],
            'commands' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'ignore_annotations' => [
                        'mixin',
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'code_executor',
                    'description' => 'code executor component configuration.', // Description
                    // It is recommended to put the default configuration in the publish folder with the same name as the component
                    'source' => __DIR__ . '/../publish/code_executor.php',  // Corresponding configuration file path
                    'destination' => BASE_PATH . '/config/autoload/code_executor.php', // Copy to this file path
                ],
            ],
        ];
    }
}
