<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\Standards;

interface RuleSessionInterface
{
    public function getRuleExecutionSetMetadata(): RuleExecutionSetMetadataInterface;

    public function release(): void;

    public function getType(): RuleSessionType;
}
