<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript;

use Delightful\RuleEngineCore\PhpScript\Admin\RuleExecutionSet;
use Delightful\RuleEngineCore\PhpScript\Repository\RuleExecutionSetRepositoryInterface;
use Delightful\RuleEngineCore\Standards\Admin\Properties;
use Delightful\RuleEngineCore\Standards\Exception\InvalidRuleSessionException;
use Delightful\RuleEngineCore\Standards\Exception\RuleExecutionSetNotFoundException;
use Delightful\RuleEngineCore\Standards\ObjectFilterInterface;
use Delightful\RuleEngineCore\Standards\RuleExecutionSetMetadataInterface;
use Delightful\RuleEngineCore\Standards\RuleSessionType;
use Delightful\RuleEngineCore\Standards\StatelessRuleSessionInterface;

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
