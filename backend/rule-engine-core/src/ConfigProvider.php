<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore;

use Delightful\RuleEngineCore\PhpScript\PlaceholderProvider;
use Delightful\RuleEngineCore\PhpScript\PlaceholderProviderInterface;
use Delightful\RuleEngineCore\PhpScript\Repository\DefaultExecutableCodeRepository;
use Delightful\RuleEngineCore\PhpScript\Repository\DefaultRuleExecutionSetRepository;
use Delightful\RuleEngineCore\PhpScript\Repository\ExecutableCodeRepositoryInterface;
use Delightful\RuleEngineCore\PhpScript\Repository\RuleExecutionSetRepositoryInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                RuleExecutionSetRepositoryInterface::class => DefaultRuleExecutionSetRepository::class,
                PlaceholderProviderInterface::class => PlaceholderProvider::class,
                ExecutableCodeRepositoryInterface::class => DefaultExecutableCodeRepository::class,
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
        ];
    }
}
