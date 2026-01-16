<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Builder;

use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Exception\FlowExprEngineException;
use Delightful\FlowExprEngine\Structure\Condition\CompareType;
use Delightful\FlowExprEngine\Structure\Condition\Condition;
use Delightful\FlowExprEngine\Structure\Condition\ConditionItem;
use Delightful\FlowExprEngine\Structure\Condition\ConditionItemType;
use Delightful\FlowExprEngine\Structure\Condition\Ops;
use Delightful\FlowExprEngine\Structure\Expression\Value;

class ConditionBuilder extends Builder
{
    public function build(array $structure): ?Condition
    {
        if (empty($structure)) {
            return null;
        }

        $ops = Ops::from($structure['ops'] ?? '');
        $children = $structure['children'] ?? null;
        if (! $children) {
            return null;
        }

        return new Condition(ops: $ops, items: $this->buildChildren($children));
    }

    public function template(string $componentId, array $structure = []): ?Condition
    {
        return $this->build($structure);
    }

    private function buildChildren(array $children): array
    {
        $items = [];
        foreach ($children as $child) {
            if (! empty($child['ops'])) {
                $items[] = $this->build($child);
            } else {
                $conditionItemType = ConditionItemType::from($child['type'] ?? '');

                $conditionItem = new ConditionItem();
                $conditionItem->setType($conditionItemType);
                $conditionItem->setTemplate(ComponentFactory::fastCreate($child['template'] ?? null));
                switch ($conditionItemType) {
                    case ConditionItemType::Operation:
                        if ($conditionItem->getTemplate()) {
                            $operands = $conditionItem->getTemplate()->getWidget()->getProperties()['operands']?->getValue();
                        } else {
                            $operands = Value::build($child['operands'] ?? null);
                        }

                        if (! $operands || $operands->isEmpty()) {
                            throw new FlowExprEngineException('Comparison value cannot be empty');
                        }
                        $conditionItem->setOperands($operands);
                        $items[] = $conditionItem;
                        break;
                    case ConditionItemType::Compare:
                        if ($conditionItem->getTemplate()) {
                            $leftOperands = $conditionItem->getTemplate()->getWidget()->getProperties()['left_operands']?->getValue();
                            $rightOperands = $conditionItem->getTemplate()->getWidget()->getProperties()['right_operands']?->getValue();
                            $compareType = CompareType::from($conditionItem->getTemplate()->getWidget()->getProperties()['condition']?->getValue()?->getResult());
                        } else {
                            $leftOperands = Value::build($child['left_operands'] ?? []);
                            $rightOperands = Value::build($child['right_operands'] ?? []);
                            $compareType = CompareType::make($child['condition'] ?? null);
                        }

                        if (! $compareType) {
                            throw new FlowExprEngineException('Comparison type cannot be empty');
                        }
                        $conditionItem->setCompareType($compareType);
                        // Left side value is required
                        if (! $leftOperands || $leftOperands->isEmpty()) {
                            throw new FlowExprEngineException('Left comparison value cannot be empty');
                        }
                        // If the right side is required, check the right side
                        if ($compareType->isRightOperandsRequired() && (! $rightOperands || $rightOperands->isEmpty())) {
                            throw new FlowExprEngineException('Right comparison value cannot be empty');
                        }
                        $conditionItem->setLeftOperands($leftOperands);
                        $conditionItem->setRightOperands($rightOperands);
                        $items[] = $conditionItem;
                        break;
                }
            }
        }
        return $items;
    }
}
