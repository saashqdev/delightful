<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\Standards;

use Dtyq\RuleEngineCore\Standards\Admin\Properties;

interface RuleRuntimeInterface
{
    public function createRuleSession(string $uri, ?Properties $properties, RuleSessionType $ruleSessionType): RuleSessionInterface;

    public function getRegistrations(): array;
}
