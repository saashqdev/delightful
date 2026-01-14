<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject;

use App\Infrastructure\Core\AbstractValueObject;
use BeDelightful\FlowExprEngine\Component;

class NodeOutput extends AbstractValueObject
{
    protected ?Component $widget = null;

    protected ?Component $form = null;

    public function getFormComponent(): ?Component
    {
        return $this->form;
    }

    public function getWidget(): ?Component
    {
        return $this->widget;
    }

    public function setWidget(?Component $widget): void
    {
        $this->widget = $widget;
    }

    public function getForm(): ?Component
    {
        return $this->form;
    }

    public function setForm(?Component $form): void
    {
        $this->form = $form;
    }
}
