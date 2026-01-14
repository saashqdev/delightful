<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\DTO\Node;

use App\Interfaces\Flow\DTO\AbstractFlowDTO;
use BeDelightful\FlowExprEngine\Component;

class NodeOutputDTO extends AbstractFlowDTO
{
    public ?Component $widget = null;

    public ?Component $form = null;

    public function getWidget(): ?Component
    {
        return $this->widget;
    }

    public function setWidget(mixed $widget): void
    {
        $this->widget = $this->createComponent($widget);
    }

    public function getForm(): ?Component
    {
        return $this->form;
    }

    public function setForm(mixed $form): void
    {
        $this->form = $this->createComponent($form);
    }
}
