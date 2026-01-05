<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Widget;

class ShowOptions
{
    private bool $desensitization;

    public function __construct(bool $desensitization = false)
    {
        $this->desensitization = $desensitization;
    }

    public function isDesensitization(): bool
    {
        return $this->desensitization;
    }
}
