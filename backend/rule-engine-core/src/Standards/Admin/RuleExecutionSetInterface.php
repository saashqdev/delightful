<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\Standards\Admin;

use JsonSerializable;

interface RuleExecutionSetInterface extends JsonSerializable
{
    public function getName(): string;

    public function getDescription(): ?string;

    public function setDefaultObjectFilter(string $objectFilterClassname): void;

    public function getDefaultObjectFilter(): string;

    public function getRules(): array;
}
