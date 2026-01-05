<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode;

interface MethodInterface
{
    public function getCode(): string;

    public function getName(): string;

    public function getReturnType(): string;

    public function getGroup(): string;

    public function getDesc(): string;

    public function getArgs(): array;

    public function getFunction(): ?callable;

    public function isHide(): bool;

    public function validate(): void;
}
