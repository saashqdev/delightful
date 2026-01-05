<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript\Admin;

interface ExecutableClassInterface extends ExecutableCodeInterface
{
    public function getNamespaceName(): string;

    public function getShortName(): string;
}
