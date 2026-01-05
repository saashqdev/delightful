<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Widget;

use Delightful\FlowExprEngine\Structure\Widget\DisplayConfigExtra\AbstractExtra;
use Delightful\FlowExprEngine\Structure\Widget\DisplayConfigExtra\NumberExtra;
use Delightful\FlowExprEngine\Structure\Widget\DisplayConfigExtra\ObjectExtra;
use Delightful\FlowExprEngine\Structure\Widget\DisplayConfigExtra\SelectExtra;
use Delightful\FlowExprEngine\Structure\Widget\DisplayConfigExtra\SwitchExtra;

class DisplayConfig
{
    /**
     * Widget name.
     */
    private string $label;

    /**
     * Widget type.
     */
    private WidgetType $widgetType;

    /**
     * Description.
     */
    private string $tooltips;

    /**
     * Whether required.
     */
    private bool $required;

    /**
     * Whether visible.
     */
    private bool $visible;

    /**
     * Whether expression is allowed.
     */
    private bool $allowExpression;

    /**
     * Whether disabled.
     */
    private bool $disabled;

    /**
     * Extended configuration.
     */
    private ?AbstractExtra $extra;

    /**
     * Used to store additional frontend configuration.
     */
    private ?array $webConfig = null;

    public static function create(?array $config, ?array $options): ?DisplayConfig
    {
        if (! $config || ! $options) {
            return null;
        }
        $widgetType = WidgetType::from($config['widget_type'] ?? null);

        $displayConfig = new self();
        $displayConfig->setLabel($config['label'] ?? '');
        $displayConfig->setWidgetType($widgetType);
        $displayConfig->setTooltips($config['tooltips'] ?? '');
        $displayConfig->setRequired((bool) ($config['required'] ?? false));
        $displayConfig->setVisible((bool) ($config['visible'] ?? true));
        $displayConfig->setAllowExpression((bool) ($config['allow_expression'] ?? true));
        $displayConfig->setDisabled((bool) ($config['disabled'] ?? false));

        $extra = match ($widgetType) {
            WidgetType::Number => NumberExtra::create($config),
            WidgetType::Switch => SwitchExtra::create($config),
            WidgetType::Select, WidgetType::Linkage => SelectExtra::create($config, $options),
            WidgetType::Object => ObjectExtra::create($config, $options),
            default => null,
        };

        $displayConfig->setExtra($extra);
        $displayConfig->setWebConfig($config['web_config'] ?? null);
        return $displayConfig;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function setWidgetType(WidgetType $widgetType): void
    {
        $this->widgetType = $widgetType;
    }

    public function setTooltips(string $tooltips): void
    {
        $this->tooltips = $tooltips;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    public function setVisible(bool $visible): void
    {
        $this->visible = $visible;
    }

    public function setAllowExpression(bool $allowExpression): void
    {
        $this->allowExpression = $allowExpression;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function setExtra(?AbstractExtra $extra): void
    {
        $this->extra = $extra;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getWidgetType(): WidgetType
    {
        return $this->widgetType;
    }

    public function getTooltips(): string
    {
        return $this->tooltips;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function isAllowExpression(): bool
    {
        return $this->allowExpression;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getExtra(): ?AbstractExtra
    {
        return $this->extra;
    }

    public function getWebConfig(): ?array
    {
        return $this->webConfig;
    }

    public function setWebConfig(?array $webConfig): void
    {
        $this->webConfig = $webConfig;
    }

    public function toArray(): array
    {
        return [
            'label' => $this->getLabel(),
            'widget_type' => $this->getWidgetType()->value,
            'tooltips' => $this->getTooltips(),
            'required' => $this->isRequired(),
            'visible' => $this->isVisible(),
            'allow_expression' => $this->isAllowExpression(),
            'disabled' => $this->isDisabled(),
            'extra' => $this->getExtra()?->toArray(),
            'web_config' => $this->getWebConfig(),
        ];
    }
}
