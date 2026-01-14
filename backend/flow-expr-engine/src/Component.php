<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine;

use BeDelightful\FlowExprEngine\Builder\ApiBuilder;
use BeDelightful\FlowExprEngine\Builder\Builder;
use BeDelightful\FlowExprEngine\Builder\ConditionBuilder;
use BeDelightful\FlowExprEngine\Builder\ExpressionBuilder;
use BeDelightful\FlowExprEngine\Builder\FormBuilder;
use BeDelightful\FlowExprEngine\Builder\ValueBuilder;
use BeDelightful\FlowExprEngine\Builder\WidgetBuilder;
use BeDelightful\FlowExprEngine\Exception\FlowExprEngineException;
use BeDelightful\FlowExprEngine\Kernel\Traits\UnderlineObjectJsonSerializable;
use BeDelightful\FlowExprEngine\Structure\Api\Api;
use BeDelightful\FlowExprEngine\Structure\Condition\Condition;
use BeDelightful\FlowExprEngine\Structure\Expression\Expression;
use BeDelightful\FlowExprEngine\Structure\Expression\Value;
use BeDelightful\FlowExprEngine\Structure\Form\Form;
use BeDelightful\FlowExprEngine\Structure\Structure;
use BeDelightful\FlowExprEngine\Structure\StructureType;
use BeDelightful\FlowExprEngine\Structure\Widget\Widget;
use JsonSerializable;

class Component implements JsonSerializable
{
    use UnderlineObjectJsonSerializable;

    /**
     * Component identifier.
     */
    private string $id;

    /**
     * Component version.
     */
    private string $version;

    /**
     * Component type.
     */
    private StructureType $type;

    /**
     * Component structure.
     */
    private ?Structure $structure = null;

    /**
     * Recorded during lazy loading.
     */
    private ?array $structureLazy = null;

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    public function jsonSerialize(): array
    {
        $structure = null;
        if ($this->structure) {
            $structure = $this->structure->toArray();
        } elseif ($this->structureLazy) {
            $structure = $this->structureLazy;
        }
        return [
            'id' => $this->getId(),
            'version' => $this->getVersion(),
            'type' => $this->getType()->value,
            'structure' => $structure,
        ];
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): Component
    {
        $this->id = $id;
        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): Component
    {
        $this->version = $version;
        return $this;
    }

    public function getType(): StructureType
    {
        return $this->type;
    }

    public function setType(StructureType $type): Component
    {
        $this->type = $type;
        return $this;
    }

    public function getStructure(): ?Structure
    {
        if ($this->structure) {
            return $this->structure;
        }
        // If there is lazy load data and no loaded data, perform a data load
        if (! is_null($this->structureLazy)) {
            $this->initStructure($this->structureLazy);
            $this->structureLazy = null;
        }
        return $this->structure;
    }

    public function setStructure(?Structure $structure): Component
    {
        $this->structure = $structure;
        return $this;
    }

    public function getStructureLazy(): ?array
    {
        return $this->structureLazy;
    }

    public function setStructureLazy(?array $structureLazy): Component
    {
        $this->structureLazy = $structureLazy;
        return $this;
    }

    /**
     * @return Expression
     */
    public function getExpression(): Structure
    {
        return $this->getSpecificStructure(StructureType::Expression, Expression::class);
    }

    /**
     * @return Form
     */
    public function getForm(): Structure
    {
        return $this->getSpecificStructure(StructureType::Form, Form::class);
    }

    /**
     * @return Condition
     */
    public function getCondition(): Structure
    {
        return $this->getSpecificStructure(StructureType::Condition, Condition::class);
    }

    /**
     * @return Api
     */
    public function getApi(): Structure
    {
        return $this->getSpecificStructure(StructureType::Api, Api::class);
    }

    /**
     * @return Value
     */
    public function getValue(): Structure
    {
        return $this->getSpecificStructure(StructureType::Value, Value::class);
    }

    /**
     * @return Widget
     */
    public function getWidget(): Structure
    {
        return $this->getSpecificStructure(StructureType::Widget, Widget::class);
    }

    public function isExpression(): bool
    {
        return $this->is(StructureType::Expression);
    }

    public function isForm(): bool
    {
        return $this->is(StructureType::Form);
    }

    public function isCondition(): bool
    {
        return $this->is(StructureType::Condition);
    }

    public function isApi(): bool
    {
        return $this->is(StructureType::Api);
    }

    public function isValue(): bool
    {
        return $this->is(StructureType::Value);
    }

    public function initStructure(null|array|Structure $structure): void
    {
        if (is_array($structure)) {
            $builder = $this->getBuilder();
            $structure = $builder->build($structure);
        }
        if ($structure instanceof Structure) {
            $structure->setComponentId($this->id);
        }
        $this->structure = $structure;
    }

    public function createTemplate(array $structure): void
    {
        $builder = $this->getBuilder();
        $structure = $builder->template($this->id, $structure);
        $this->structure = $structure;
    }

    private function is(StructureType $type): bool
    {
        return $this->getType() === $type;
    }

    private function getSpecificStructure(StructureType $type, string $componentClass): Structure
    {
        $name = $type->name;
        if ($this->getType() !== $type) {
            throw new FlowExprEngineException("Component is not {$name}");
        }
        $specificStructure = $this->getStructure();
        if (is_null($specificStructure)) {
            // Generate default component format
            $specificStructure = $this->getBuilder()->template($this->id);
        }
        if (! $specificStructure instanceof $componentClass) {
            throw new FlowExprEngineException("Component is not {$name}.");
        }
        return $specificStructure;
    }

    private function getBuilder(): ?Builder
    {
        return match ($this->type) {
            StructureType::Expression => new ExpressionBuilder(),
            StructureType::Form => new FormBuilder(),
            StructureType::Widget => new WidgetBuilder(),
            StructureType::Condition => new ConditionBuilder(),
            StructureType::Api => new ApiBuilder(),
            StructureType::Value => new ValueBuilder(),
            default => null,
        };
    }
}
