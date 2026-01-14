<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Builder;

use BeDelightful\FlowExprEngine\Structure\Expression\Expression;
use BeDelightful\FlowExprEngine\Structure\Expression\ExpressionItem;
use BeDelightful\FlowExprEngine\Structure\Expression\ExpressionType;
use BeDelightful\FlowExprEngine\Structure\Expression\Value;
use BeDelightful\FlowExprEngine\Structure\Expression\ValueType;

class ExpressionBuilder extends Builder
{
    public function build(array $structure): ?Expression
    {
        $structures = $structure;
        if (empty($structures)) {
            return null;
        }
        $items = [];
        $structures = array_values($structures);
        $count = count($structures);
        foreach ($structures as $i => $structure) {
            $item = $this->buildItem($structure);
            if (! $item) {
                continue;
            }
            if (in_array($i, [0, $count - 1], true)) {
                // If it's input and value is empty, skip it. And it must be at the beginning or end
                if ($item->getType() === ExpressionType::Input && is_string($item->getValue()) && trim($item->getValue()) === '') {
                    continue;
                }
            }
            $items[] = $item;
        }
        if (empty($items)) {
            return null;
        }
        return new Expression(items: $items);
    }

    public function template(string $componentId, array $structure = []): ?Expression
    {
        return $this->build($structure);
    }

    private function buildItem(array $structure): ?ExpressionItem
    {
        $type = ExpressionType::make($structure['type'] ?? null);
        if (! $type) {
            return null;
        }
        $value = $structure['value'] ?? null;
        if (is_null($value) && ! $type->isDisplayValue()) {
            return null;
        }
        $valueType = ValueType::tryFrom($structure['value_type'] ?? '') ?? ValueType::Expression;

        $args = null;
        foreach ($structure['args'] ?? [] as $arg) {
            $arg = Value::build($arg);
            $arg && $args[] = $arg;
        }

        $item = new ExpressionItem(
            type: $type,
            value: $value,
            name: $structure['name'] ?? '',
            args: $args,
            valueType: $valueType,
            trans: $structure['trans'] ?? null,
        );
        if ($type->isDisplayValue()) {
            $item->setDisplayValue($structure[$type->value . '_value'] ?? null);
        }
        return $item;
    }
}
