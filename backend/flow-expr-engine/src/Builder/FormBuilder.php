<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Builder;

use Delightful\FlowExprEngine\Exception\FlowExprEngineException;
use Delightful\FlowExprEngine\Structure\Expression\DataType;
use Delightful\FlowExprEngine\Structure\Expression\Value;
use Delightful\FlowExprEngine\Structure\Form\Form;
use Delightful\FlowExprEngine\Structure\Form\FormType;

class FormBuilder extends Builder
{
    public function build(array $structure): ?Form
    {
        if (empty($structure)) {
            return null;
        }
        $root = $this->buildRoot($structure);
        if (! $root) {
            return null;
        }
        $this->buildChildren($root, $structure);

        // TODO: Check if the value format is correct; when it's an array, properties and items format should be consistent

        return $root;
    }

    public function template(string $componentId, array $structure = []): ?Form
    {
        $template = json_decode(
            <<<'JSON'
{
    "type": "object",
    "key": "root",
    "sort": 0,
    "title": null,
    "description": null,
    "items": null,
    "value": null,
    "required": [
    ],
    "properties": null
}
JSON
            ,
            true
        );
        if (! empty($structure)) {
            $template = $structure;
        }
        return $this->build($template);
    }

    private function buildRoot(array $array): ?Form
    {
        // Root node, the starting type should be array or object
        $rootType = FormType::tryFrom($array['type'] ?? null);
        if (! in_array($rootType, [FormType::Array, FormType::Object])) {
            return null;
        }

        $root = new Form(
            type: $rootType,
            key: Form::ROOT_KEY,
            sort: 0,
            title: $array['title'] ?? null,
            description: $array['description'] ?? null,
            required: $array['required'] ?? null,
        );
        // Root can also set value
        if ($rootType->isComplex() && ! empty($array['value'])) {
            $value = Value::build($array['value']);
            if (! empty($array['items']['type'])) {
                // Array value has a type
                $itemType = FormType::tryFrom($array['items']['type']);
                $value?->setDataType(DataType::make($itemType->value));
            }
            $root->setValue($value);
        }
        return $root;
    }

    private function buildChildren(Form $parent, array $data): void
    {
        $items = null;
        if ($parent->getType()->isArray()) {
            // Compatible with multiple ways to get items
            $items = $data['items'] ?? [];
            if (empty($items)) {
                $items = $data['properties'][0] ?? [];
            }
            if (isset($items['type']) && $items['type'] === 'object' && empty($items['properties'])) {
                $items = $data['properties'][0] ?? [];
            }
        }
        // Handle items
        if ($dataItems = $items) {
            $itemForm = new Form(
                type: FormType::from($dataItems['type'] ?? ''),
                key: $dataItems['key'] ?? '',
                sort: 0,
                title: $dataItems['title'] ?? null,
                description: $dataItems['description'] ?? null,
                required: $dataItems['required'] ?? null,
                encryption: $dataItems['encryption'] ?? false,
                encryptionValue: $dataItems['encryption_value'] ?? null,
            );
            if ($itemForm->getType()->isComplex()) {
                $this->buildChildren($itemForm, $dataItems);
            }
            $parent->setItems($itemForm);
        }

        // Handle properties
        $properties = null;
        if ($dataProperties = $data['properties'] ?? []) {
            // Initialize sorting
            $i = 0;
            foreach ($dataProperties as &$item) {
                $sort = max($item['sort'] ?? 0, $i);
                $i = $sort + 1;
                $item['sort'] = $item['sort'] ?? $i;
            }
            unset($item);
            // If submitted data has sort, sort first
            $sort = array_column($dataProperties, 'sort');
            if (! empty($sort)) {
                array_multisort($sort, SORT_ASC, $dataProperties);
            }

            $newSort = 0;
            foreach ($dataProperties as $key => $property) {
                if (is_numeric($key)) {
                    $key = (string) $key;
                }
                if (! is_string($key)) {
                    // String type is required
                    throw new FlowExprEngineException("{$key} must be string");
                }
                $propertyType = FormType::from($property['type'] ?? '');

                $propertyForm = new Form(
                    type: $propertyType,
                    key: $key,
                    sort: $newSort++,
                    title: $property['title'] ?? null,
                    description: $property['description'] ?? null,
                    required: $property['required'] ?? null,
                    encryption: $property['encryption'] ?? false,
                    encryptionValue: $property['encryption_value'] ?? null,
                );

                if (! empty($property['value'])) {
                    $value = Value::build($property['value']);
                    $value?->setDataType(DataType::make($propertyForm->getType()->value));
                    $propertyForm->setValue($value);
                }
                if ($propertyType->isComplex()) {
                    self::buildChildren($propertyForm, $property);
                }
                $properties[$key] = $propertyForm;
            }
        }
        $parent->setProperties($properties);
    }
}
