<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript\Repository;

use BeDelightful\RuleEngineCore\Standards\Admin\Properties;
use BeDelightful\RuleEngineCore\Standards\Admin\RuleExecutionSetInterface;

interface RuleExecutionSetRepositoryInterface
{
    public function getRegistrations(): array;

    public function getRuleExecutionSet(string $bindUri, ?Properties $properties = null): ?RuleExecutionSetInterface;

    public function registerRuleExecutionSet(string $bindUri, RuleExecutionSetInterface $ruleSet, ?Properties $properties = null): void;

    public function unregisterRuleExecutionSet(string $bindUri, ?Properties $properties = null): void;
}
