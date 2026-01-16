<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine;

use Delightful\FlowExprEngine\Structure\Condition\Condition;
use Delightful\FlowExprEngine\Structure\Expression\Expression;

interface RuleEngineClientInterface
{
    public function execute(string $code, array $data): mixed;

    public function isEffective(string $code): bool;

    public function getCodeByExpression(Expression $expression): string;

    public function getCodeByCondition(Condition $condition): string;
}
