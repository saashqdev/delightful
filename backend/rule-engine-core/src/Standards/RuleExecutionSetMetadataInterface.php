<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\Standards;

interface RuleExecutionSetMetadataInterface
{
    public function getUri(): string;

    public function getName(): string;

    public function getDescription(): string;
}
