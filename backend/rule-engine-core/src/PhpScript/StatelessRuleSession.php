<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript;

use BeDelightful\RuleEngineCore\PhpScript\Admin\RuleExecutionSet;
use BeDelightful\RuleEngineCore\PhpScript\Repository\RuleExecutionSetRepositoryInterface;
use BeDelightful\RuleEngineCore\Standards\Admin\Properties;
use BeDelightful\RuleEngineCore\Standards\Exception\InvalidRuleSessionException;
use BeDelightful\RuleEngineCore\Standards\Exception\RuleExecutionSetNotFoundException;
use BeDelightful\RuleEngineCore\Standards\ObjectFilterInterface;
use BeDelightful\RuleEngineCore\Standards\RuleExecutionSetMetadataInterface;
use BeDelightful\RuleEngineCore\Standards\RuleSessionType;
use BeDelightful\RuleEngineCore\Standards\StatelessRuleSessionInterface;

class StatelessRuleSession implements StatelessRuleSessionInterface
{
    private RuleExecutionSetRepositoryInterface $executionSetRepository;

    private ?RuleExecutionSet $ruleExecutionSet;

    private ?Properties $properties;

    public function __construct(
        string $bindUri,
        ?Properties $properties,
        RuleExecutionSetRepositoryInterface $executionSetRepository,
    ) {
        $this->executionSetRepository = $executionSetRepository;
        $this->properties = $properties;

        $ruleSet = $executionSetRepository->getRuleExecutionSet($bindUri, $properties);
        if ($ruleSet == null) {
            throw new RuleExecutionSetNotFoundException('Rule execution set unbound', 1005004);
        }
        $this->ruleExecutionSet = $ruleSet;
    }

    public function getAsts(): array
    {
        $this->ruleExecutionSet
            ->replacePlaceholder($this->properties->getPlaceholders())
            ->parse();

        return $this->ruleExecutionSet->getAsts();
    }

    public function executeRules(array $facts, ?ObjectFilterInterface $filter = null): array
    {
        //        if (empty($facts)
        //            || array_diff_assoc($this->ruleExecutionSet->getEntryFacts(), array_keys($facts))
        //        ) {
        //            throw new InvalidRuleSessionException('Rule execution failure:incorrect fact', 1005005);
        //        }
        return $this->ruleExecutionSet->execute($facts, $this->properties->getPlaceholders());
    }

    public function getRuleExecutionSetMetadata(): RuleExecutionSetMetadataInterface
    {
        // TODO: Implement getRuleExecutionSetMetadata() method.
    }

    public function release(): void
    {
        $this->properties = null;
        $this->ruleExecutionSet = null;
    }

    public function getType(): RuleSessionType
    {
        return RuleSessionType::from(RuleSessionType::Stateless);
    }
}
