<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Builder;

use Delightful\FlowExprEngine\Structure\Structure;

abstract class Builder
{
    abstract public function build(array $structure): ?Structure;

    abstract public function template(string $componentId, array $structure = []): ?Structure;
}
