<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\Options\ExecuteOption;

use BeDelightful\RuleEngineCore\PhpScript\Admin\RuleExecutionSetProperties;
use BeDelightful\RuleEngineCore\Standards\Admin\InputType;
use BeDelightful\RuleEngineCore\Standards\RuleSessionType;

abstract class AbstractExecuteOption
{
    protected string $name;

    protected string $namePrefix = 'rule-engine-';

    public function __construct()
    {
        $this->generateName();
    }

    public function getName(): string
    {
        return $this->name;
    }

    abstract public function getUri(): string;

    abstract public function getInputType(): InputType;

    abstract public function getRuleSessionType(): RuleSessionType;

    abstract public function getRuleExecutionSetProperties(): RuleExecutionSetProperties;

    protected function generateName(): void
    {
        $this->name = uniqid($this->namePrefix);
    }
}
