<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Condition;

use BeDelightful\FlowExprEngine\Component;
use BeDelightful\FlowExprEngine\Structure\Expression\Value;

class ConditionItem
{
    private ConditionItemType $type;

    private ?Component $template = null;

    /**
     * Takes effect when type is operation.
     */
    private ?Value $operands = null;

    /**
     * condition
     * Takes effect when type is operation.
     */
    private ?CompareType $compareType = null;

    /**
     * Takes effect when type is compare.
     */
    private ?Value $leftOperands = null;

    /**
     * Takes effect when type is compare.
     */
    private ?Value $rightOperands = null;

    public function toArray(): array
    {
        return match ($this->getType()) {
            ConditionItemType::Operation => [
                'type' => $this->getType()->value,
                'template' => $this->getTemplate()?->toArray(),
                'operands' => $this->getOperands()->jsonSerialize(),
            ],
            ConditionItemType::Compare => [
                'type' => $this->getType()->value,
                'template' => $this->getTemplate()?->toArray(),
                'left_operands' => $this->getLeftOperands()->jsonSerialize(),
                'condition' => $this->getCompareType()->value,
                'right_operands' => $this->getRightOperands()?->jsonSerialize(),
            ],
        };
    }

    public function getType(): ConditionItemType
    {
        return $this->type;
    }

    public function setType(ConditionItemType $type): void
    {
        $this->type = $type;
    }

    public function getTemplate(): ?Component
    {
        return $this->template;
    }

    public function setTemplate(?Component $template): ConditionItem
    {
        $this->template = $template;
        return $this;
    }

    public function getOperands(): ?Value
    {
        return $this->operands;
    }

    public function setOperands(?Value $operands): void
    {
        $this->operands = $operands;
    }

    public function getCompareType(): ?CompareType
    {
        return $this->compareType;
    }

    public function setCompareType(?CompareType $compareType): void
    {
        $this->compareType = $compareType;
    }

    public function getLeftOperands(): ?Value
    {
        return $this->leftOperands;
    }

    public function setLeftOperands(?Value $leftOperands): void
    {
        $this->leftOperands = $leftOperands;
    }

    public function getRightOperands(): ?Value
    {
        return $this->rightOperands;
    }

    public function setRightOperands(?Value $rightOperands): void
    {
        $this->rightOperands = $rightOperands;
    }
}
