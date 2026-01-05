<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\Standards;

interface ObjectFilterInterface
{
    public function filter(object $var1): object;

    public function reset(): void;
}
