<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript;

use BeDelightful\RuleEngineCore\PhpScript\Repository\RuleExecutionSetRepositoryInterface;
use BeDelightful\RuleEngineCore\Standards\Admin\Properties;
use BeDelightful\RuleEngineCore\Standards\Exception\RuleSessionTypeUnsupportedException;
use BeDelightful\RuleEngineCore\Standards\RuleRuntimeInterface;
use BeDelightful\RuleEngineCore\Standards\RuleSessionInterface;
use BeDelightful\RuleEngineCore\Standards\RuleSessionType;

class RuleRuntime implements RuleRuntimeInterface
{
    public function __construct(
        private RuleExecutionSetRepositoryInterface $executionSetRepository,
    ) {
    }

    public function createRuleSession(string $uri, ?Properties $properties, RuleSessionType $ruleSessionType): RuleSessionInterface
    {
        return match ($ruleSessionType->value) {
            RuleSessionType::Stateless => new StatelessRuleSession($uri, $properties, $this->executionSetRepository),
            default => throw new RuleSessionTypeUnsupportedException('invalid session type:' . $ruleSessionType->name, 1007),
        };
    }

    public function getRegistrations(): array
    {
        // TODO: Implement getRegistrations() method.
        return [];
    }
}
