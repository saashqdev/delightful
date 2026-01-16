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
use Delightful\FlowExprEngine\Structure\Widget\DisplayConfig;
use Delightful\FlowExprEngine\Structure\Widget\ShowOptions;
use Delightful\FlowExprEngine\Structure\Widget\Widget;

class WidgetBuilder extends Builder
{
    private ?ShowOptions $showOptions;

    public function __construct(?ShowOptions $showOptions = null)
    {
        $this->showOptions = $showOptions;
    }

    public function build(array $structure): ?Widget
    {
        if (! $structure) {
            return null;
        }

        $root = $this->buildRoot($structure);
        if (! $root) {
            return null;
        }
        $this->buildChildren($root, $structure);
        return $root;
    }

    public function template(string $componentId, array $structure = []): ?Widget
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
    "initial_value": null,
    "display_config": null,
    "properties": null
}
JSON,
            true
        );
        if (! empty($structure)) {
            $template = $structure;
        }
        return $this->build($template);
    }

    private function buildRoot(array $array): ?Widget
    {
        // Root node, starting type should be array or object
        $rootType = FormType::from($array['type'] ?? null);
        if (! in_array($rootType, [FormType::Array, FormType::Object])) {
            return null;
        }

        $root = new Widget(
            type: $rootType,
            key: Form::ROOT_KEY,
            sort: 0,
            title: $array['title'] ?? '',
            description: $array['description'] ?? '',
            showOptions: $this->showOptions
        );
        // Root can also set value
        if ($rootType->isComplex() && ! empty($array['value'])) {
            $value = Value::build($array['value']);
            if (! empty($array['items']['type'])) {
                // Array value has a type
                $itemType = FormType::from($array['items']['type']);
                $value?->setDataType(DataType::make($itemType->value));
            }
            $root->setValue($value);
        }
        return $root;
    }

    private function buildChildren(Widget $parent, array $data): void
    {
        // Handle items
        if ($dataItems = $data['items'] ?? []) {
            $widgetItems = new Widget(
                type: FormType::from($dataItems['type'] ?? ''),
                key: $dataItems['key'] ?? '',
                sort: 0,
                title: $dataItems['title'] ?? '',
                description: $dataItems['description'] ?? '',
                showOptions: $this->showOptions
            );
            $this->createValues($widgetItems, $dataItems);
            $widgetItems->setDisplayConfig(DisplayConfig::create($dataItems['display_config'] ?? null, [
                'action_field_slug' => $widgetItems->getKey(),
            ]));

            if ($widgetItems->getType()->isComplex()) {
                $this->buildChildren($widgetItems, $dataItems);
            }
            $parent->setItems($widgetItems);
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

                $propertyWidget = new Widget(
                    type: $propertyType,
                    key: $key,
                    sort: $newSort++,
                    title: $property['title'] ?? '',
                    description: $property['description'] ?? '',
                    showOptions: $this->showOptions
                );
                $this->createValues($propertyWidget, $property);
                $propertyWidget->setDisplayConfig(DisplayConfig::create($property['display_config'] ?? null, [
                    'action_field_slug' => $key,
                ]));

                if ($propertyType->isComplex()) {
                    $this->buildChildren($propertyWidget, $property);
                }
                $properties[$key] = $propertyWidget;
            }
        }
        $parent->setProperties($properties);
    }

    private function createValues(Widget $widget, array $data): void
    {
        $value = Value::build($data['value'] ?? []);
        $value?->setDataType(DataType::make($widget->getType()->value));
        $widget->setValue($value);

        $initialValue = Value::build($data['initial_value'] ?? []);
        $initialValue?->setDataType(DataType::make($widget->getType()->value));
        $widget->setInitialValue($initialValue);
    }
}
