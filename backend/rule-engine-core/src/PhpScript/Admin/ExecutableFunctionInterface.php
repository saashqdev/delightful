<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\RuleEngineCore\PhpScript\Admin;

use Closure;

interface ExecutableFunctionInterface extends ExecutableCodeInterface
{
    public function getFunction(): Closure;
}
