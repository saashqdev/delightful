<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore;

use BeDelightful\RuleEngineCore\PhpScript\PlaceholderProvider;
use BeDelightful\RuleEngineCore\PhpScript\PlaceholderProviderInterface;
use BeDelightful\RuleEngineCore\PhpScript\Repository\DefaultExecutableCodeRepository;
use BeDelightful\RuleEngineCore\PhpScript\Repository\DefaultRuleExecutionSetRepository;
use BeDelightful\RuleEngineCore\PhpScript\Repository\ExecutableCodeRepositoryInterface;
use BeDelightful\RuleEngineCore\PhpScript\Repository\RuleExecutionSetRepositoryInterface;

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
