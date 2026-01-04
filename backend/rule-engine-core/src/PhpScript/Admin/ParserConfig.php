<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace Dtyq\RuleEngineCore\PhpScript\Admin;

class ParserConfig
{
    // Whether to allow users to declare classes
    public bool $allowDeclareClasses = false;

    public function isAllowDeclareClasses(): bool
    {
        return $this->allowDeclareClasses;
    }

    public function setAllowDeclareClasses(bool $allowDeclareClasses): ParserConfig
    {
        $this->allowDeclareClasses = $allowDeclareClasses;
        return $this;
    }
}
