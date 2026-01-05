<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\RuleEngineCore\Standards;

interface StatelessRuleSessionInterface extends RuleSessionInterface
{
    public function executeRules(array $facts, ?ObjectFilterInterface $filter = null): array;
}
