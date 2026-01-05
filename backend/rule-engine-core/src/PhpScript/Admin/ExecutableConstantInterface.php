<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript\Admin;

interface ExecutableConstantInterface extends ExecutableCodeInterface
{
    public function getConstantName(): string;

    public function getConstantValue(): mixed;

    public function isSystemConstant(): bool;
}
