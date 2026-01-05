<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript\Admin;

interface ExecutableCodeInterface
{
    public function getType(): ExecutableType;

    public function getName(): string;

    public function getRuleGroup(): string;
}
