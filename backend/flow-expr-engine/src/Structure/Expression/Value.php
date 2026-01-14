<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Expression;

use BeDelightful\FlowExprEngine\Builder\ExpressionBuilder;
use BeDelightful\FlowExprEngine\Exception\FlowExprEngineException;
use BeDelightful\FlowExprEngine\Kernel\Traits\UnderlineObjectJsonSerializable;
use BeDelightful\FlowExprEngine\Structure\Structure;
use BeDelightful\FlowExprEngine\Structure\StructureType;
use JsonSerializable;

class Value extends Structure implements JsonSerializable
{
    use UnderlineObjectJsonSerializable;

    public StructureType $structureType = StructureType::Value;

    protected ValueType $type;

    protected ?Expression $constValue = null;

    protected ?Expression $expressionValue = null;

    private ?DataType $dataType;

    public function __construct(
        ValueType $type,
        ?Expression $constValue = null,
        ?Expression $expressionValue = null,
        ?DataType $dataType = null
    ) {
        $this->type = $type;
        if (! $constValue?->isOldConstValue()) {
            $constValue?->setIsStringTemplate(true);
        }
        $this->constValue = $constValue;
        $this->expressionValue = $expressionValue;
        $this->dataType = $dataType;
    }

    public static function buildConst(mixed $input): ?Value
    {
        if (is_null($input)) {
            return null;
        }
        $data = [
            'type' => ValueType::Const->value,
            'const_value' => [
                [
                    'type' => ExpressionType::Input->value,
                    'value' => $input,
                    'name' => 'append_const_value',
                    'args' => null,
                ],
            ],
            'expression_value' => null,
        ];
        return self::build($data);
    }

    public static function buildExpression(string $input): ?Value
    {
        if ($input === '') {
            return null;
        }
        $data = [
            'type' => ValueType::Expression->value,
            'const_value' => [],
            'expression_value' => [
                [
                    'type' => ExpressionType::Field->value,
                    'value' => $input,
                    'name' => '',
                    'args' => null,
                ],
            ],
        ];
        return self::build($data);
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type->value,
            'const_value' => $this->getConstValue()?->toArray(),
            'expression_value' => $this->getExpressionValue()?->toArray(),
        ];
    }

    public function getAllFieldsExpressionItem(): array
    {
        return match ($this->getType()) {
            ValueType::Const => $this->getConstValue()?->getAllFieldsExpressionItem() ?? [],
            ValueType::Expression => $this->getExpressionValue()?->getAllFieldsExpressionItem() ?? [],
        };
    }

    public function getType(): ValueType
    {
        return $this->type;
    }

    public function getConstValue(): ?Expression
    {
        return $this->constValue;
    }

    public function getExpressionValue(): ?Expression
    {
        return $this->expressionValue;
    }

    public static function build(array $data): ?Value
    {
        if (! $data) {
            return null;
        }
        $dataType = DataType::make($data['data_type'] ?? '');

        $type = ValueType::tryFrom($data['type'] ?? '');
        $expressionBuilder = new ExpressionBuilder();
        switch ($type) {
            case ValueType::Const:
                $constValue = $data['const_value'] ?? [];
                foreach ($constValue as &$item) {
                    $item['value_type'] = $item['value_type'] ?? ValueType::Const->value;
                }
                unset($item);
                $constValue = $expressionBuilder->build($constValue);
                if (! $constValue) {
                    // Data might be in expression at this point, try to get it
                    $constValue = $expressionBuilder->build($data['expression_value'] ?? []);
                    if (! $constValue) {
                        $constValue = new Expression([new ExpressionItem(ExpressionType::Input, '', '')]);
                    }
                }
                return new Value($type, $constValue, null, $dataType);
            case ValueType::Expression:
                $expressionValue = $expressionBuilder->build($data['expression_value'] ?? []);
                if (! $expressionValue) {
                    // Data might be in const value at this point, try to get it
                    $expressionValue = $expressionBuilder->build($data['const_value'] ?? []);
                    if (! $expressionValue) {
                        $expressionValue = new Expression([new ExpressionItem(ExpressionType::Input, '', '')]);
                    }
                    $expressionValue->setIsStringTemplate(true);
                }
                return new Value($type, null, $expressionValue, $dataType);
            default:
                return null;
        }
    }

    public function expressionIsOnlyFields(?string $filterValue = null): bool
    {
        if ($this->getType() !== ValueType::Expression) {
            return false;
        }
        if (! $this->getExpressionValue()) {
            // No value is equivalent to null
            return true;
        }
        // Just ensure the first one is Field type
        $firstItem = $this->getExpressionValue()->getItems()[0];
        if ($firstItem->getType() !== ExpressionType::Field) {
            return false;
        }
        if ($filterValue && $firstItem->getValue() !== $filterValue) {
            return false;
        }
        return true;
    }

    public function expressionIsOnlyMethod(): bool
    {
        if ($this->getType() !== ValueType::Expression) {
            return false;
        }
        if (! $this->getExpressionValue()) {
            // No value is equivalent to null
            return false;
        }
        // Just ensure the first item is of Method type
        $firstItem = $this->getExpressionValue()->getItems()[0];
        if ($firstItem->getType() !== ExpressionType::Method) {
            return false;
        }
        // This function needs to return an array
        return true;
    }

    public function isConstNumber(): bool
    {
        if ($this->getType() !== ValueType::Const) {
            return false;
        }
        if (! $this->constValue?->isOldConstValue()) {
            return false;
        }
        $value = $this->constValue->getResultByConstValue();
        if (is_numeric($value)) {
            return true;
        }
        return false;
    }

    public function setDataType(?DataType $dataType): void
    {
        $this->dataType = $dataType;
    }

    public function isExpression(): bool
    {
        return $this->type == ValueType::Expression;
    }

    public function isOldConstValue(): bool
    {
        return $this->getConstValue()?->isOldConstValue() ?? false;
    }

    public function getResult(array $sourceData = [], bool $check = true, bool $execExpression = true, string $label = ''): mixed
    {
        switch ($this->getType()) {
            case ValueType::Const:
                if ($this->getConstValue()?->isOldConstValue()) {
                    $result = $this->getConstValue()?->getResultByConstValue();
                } elseif ($this->getConstValue()?->isDisplayConstValue()) {
                    $result = $this->getConstValue()?->getResultByDisplayValue($sourceData);
                } else {
                    $result = $this->getConstValue()?->getResult($sourceData, $execExpression);
                }
                break;
            case ValueType::Expression:
                // If there is only one input, return it as const
                if ($this->getExpressionValue()?->isOldConstValue()) {
                    $result = $this->getExpressionValue()?->getResultByConstValue();
                } else {
                    $result = $this->getExpressionValue()?->getResult($sourceData, $execExpression);
                }
                break;
            default:
                return null;
        }
        if (is_null($result)) {
            return null;
        }
        return $this->formatValue($result, $check, $label);
    }

    public function getExpressionRunString(bool $warpUp = false): ?string
    {
        return match ($this->type) {
            ValueType::Const => $this->constValue?->getCode($warpUp),
            ValueType::Expression => $this->expressionValue?->getCode($warpUp),
            default => '',
        };
    }

    public function isEmpty(): bool
    {
        $value = match ($this->type) {
            ValueType::Const => $this->getConstValue(),
            ValueType::Expression => $this->getExpressionValue(),
            default => null,
        };
        return empty($value);
    }

    private function formatValue(mixed $value, bool $check = true, string $label = ''): mixed
    {
        if (is_null($this->dataType)) {
            return $value;
        }
        $valueType = gettype($value);
        switch ($this->dataType) {
            case DataType::String:
                // Only allow numbers and strings
                if (is_numeric($value) || is_string($value)) {
                    $value = (string) $value;
                } else {
                    if ($check) {
                        throw new FlowExprEngineException("{$label} result is {$valueType}, cannot be converted to string");
                    }
                    $value = null;
                }
                break;
            case DataType::Number:
                // Only allow string, int, float
                if (is_numeric($value) || is_string($value)) {
                    if (is_string($value) && ! is_numeric($value)) {
                        $value = (int) $value;
                    }
                    // Uniformly use strings to represent numbers
                    $value = (string) $value;
                } else {
                    if ($check) {
                        throw new FlowExprEngineException("{$label} result is {$valueType}, cannot be converted to number");
                    }
                    $value = null;
                }
                break;
            case DataType::Array:
                if (! is_array($value)) {
                    if ($check) {
                        throw new FlowExprEngineException("{$label} result is {$valueType}, cannot be converted to array");
                    }
                    $value = null;
                }
                break;
            case DataType::Object:
                if (! is_array($value)) {
                    if ($check) {
                        throw new FlowExprEngineException("{$label} result is {$valueType}, cannot be converted to object");
                    }
                    $value = null;
                }
                break;
            case DataType::Boolean:
                if (is_string($value)) {
                    if ($value === 'true') {
                        $value = true;
                    }
                    if ($value === 'false') {
                        $value = false;
                    }
                }
                // Force cast to bool
                $value = (bool) $value;
                break;
            case DataType::Null:
                // Force cast to null
                $value = null;
                break;
            case DataType::Expression:
                break;
            default:
                $value = null;
        }
        return $value;
    }
}
