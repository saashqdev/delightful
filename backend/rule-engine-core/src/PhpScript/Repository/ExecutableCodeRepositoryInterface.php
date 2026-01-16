<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript\Repository;

use Delightful\RuleEngineCore\PhpScript\Admin\ExecutableCodeInterface;
use Delightful\RuleEngineCore\PhpScript\Admin\ExecutableType;
use Delightful\RuleEngineCore\Standards\Admin\Properties;

interface ExecutableCodeRepositoryInterface
{
    public function getRegistrations(): array;

    public function registerExecutableCode(ExecutableCodeInterface $executableCode, ?Properties $properties = null): void;

    public function unregisterExecutableCode(ExecutableType $executableType, string $name, ?string $ruleGroup = null, ?Properties $properties = null): void;

    /**
     * @return ExecutableCodeInterface[]
     */
    public function getExecutableCodesByType(ExecutableType $executableType, ?string $ruleGroup = null, ?Properties $properties = null): array;
}
