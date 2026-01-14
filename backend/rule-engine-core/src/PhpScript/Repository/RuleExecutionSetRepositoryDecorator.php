<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript\Repository;

use BeDelightful\RuleEngineCore\Standards\Admin\Properties;
use BeDelightful\RuleEngineCore\Standards\Admin\RuleExecutionSetInterface;

abstract class RuleExecutionSetRepositoryDecorator implements RuleExecutionSetRepositoryInterface
{
    protected RuleExecutionSetRepositoryInterface $wrapped;

    public function __construct(RuleExecutionSetRepositoryInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public function getRegistrations(): array
    {
        return $this->wrapped->getRegistrations();
    }

    public function getRuleExecutionSet(string $bindUri, ?Properties $properties = null): ?RuleExecutionSetInterface
    {
        return $this->wrapped->getRuleExecutionSet($bindUri, $properties);
    }

    public function registerRuleExecutionSet(string $bindUri, RuleExecutionSetInterface $ruleSet, ?Properties $properties = null): void
    {
        $this->wrapped->registerRuleExecutionSet($bindUri, $ruleSet, $properties);
    }

    public function unregisterRuleExecutionSet(string $bindUri, ?Properties $properties = null): void
    {
        $this->wrapped->unregisterRuleExecutionSet($bindUri, $properties);
    }
}
