<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\PHPCodeExecutor;

use BeDelightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\Options\ExecuteOption\AbstractExecuteOption;
use BeDelightful\FlowExprEngine\SdkInfo;
use BeDelightful\RuleEngineCore\PhpScript\Admin\RuleExecutionSetProperties;
use BeDelightful\RuleEngineCore\PhpScript\RuleType;
use BeDelightful\RuleEngineCore\Standards\Admin\InputType;
use BeDelightful\RuleEngineCore\Standards\RuleSessionType;

class CodeExecuteOption extends AbstractExecuteOption
{
    public function getUri(): string
    {
        return SdkInfo::RULE_SERVICE_PROVIDER;
    }

    public function getInputType(): InputType
    {
        return InputType::from(InputType::String);
    }

    public function getRuleSessionType(): RuleSessionType
    {
        return RuleSessionType::from(RuleSessionType::Stateless);
    }

    public function getRuleExecutionSetProperties(): RuleExecutionSetProperties
    {
        $ruleExecutionSetProperties = new RuleExecutionSetProperties();
        $ruleExecutionSetProperties->setName($this->name);
        $ruleExecutionSetProperties->setRuleType(RuleType::Script);
        return $ruleExecutionSetProperties;
    }
}
