<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\Methods;

use Closure;
use BeDelightful\FlowExprEngine\Exception\FlowExprEngineException;
use BeDelightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\ExecutableCode\MethodInterface;

abstract class AbstractMethod implements MethodInterface
{
    protected string $code;

    protected string $name = '';

    protected string $returnType = '';

    protected string $group = 'Default';

    protected string $desc = '';

    protected array $args = [];

    protected bool $hide = false;

    protected ?Closure $function = null;

    public function validate(): void
    {
        if (empty($this->code)) {
            throw new FlowExprEngineException('code is required');
        }
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getReturnType(): string
    {
        return $this->returnType;
    }

    public function setReturnType(string $returnType): void
    {
        $this->returnType = $returnType;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function setGroup(string $group): void
    {
        $this->group = $group;
    }

    public function getDesc(): string
    {
        return $this->desc;
    }

    public function setDesc(string $desc): void
    {
        $this->desc = $desc;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

    public function getFunction(): ?callable
    {
        return $this->function;
    }

    public function isHide(): bool
    {
        return $this->hide;
    }

    public function setHide(bool $hide): void
    {
        $this->hide = $hide;
    }

    public function setFunction(?Closure $function): void
    {
        $this->function = $function;
    }
}
