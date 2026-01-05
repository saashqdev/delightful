<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Widget\DisplayConfigExtra;

class SwitchExtra extends AbstractExtra
{
    private string $checkedText;

    private string $unCheckedText;

    public function __construct(string $checkedText, string $unCheckedText)
    {
        $this->checkedText = $checkedText;
        $this->unCheckedText = $unCheckedText;
    }

    public function toArray(): array
    {
        return [
            'checked_text' => $this->checkedText,
            'unchecked_text' => $this->unCheckedText,
        ];
    }

    public static function create(array $config, array $options = []): AbstractExtra
    {
        return new self(
            (string) ($config['extra']['checked_text'] ?? 'Yes'),
            (string) ($config['extra']['unchecked_text'] ?? 'No')
        );
    }
}
