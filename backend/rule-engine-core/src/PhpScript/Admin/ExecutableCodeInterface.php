<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\RuleEngineCore\PhpScript\Admin;

interface ExecutableCodeInterface
{
    public function getType(): ExecutableType;

    public function getName(): string;

    public function getRuleGroup(): string;
}
