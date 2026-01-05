<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\RuleEngineCore\PhpScript;

interface PlaceholderProviderInterface
{
    public function resolve($rules): array;

    public function replace($ruleName, $rules, $context): array;
}
