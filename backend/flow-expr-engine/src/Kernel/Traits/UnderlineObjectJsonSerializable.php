<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Kernel\Traits;

use Dtyq\FlowExprEngine\Kernel\Utils\Functions;

trait UnderlineObjectJsonSerializable
{
    public function jsonSerialize(): array
    {
        $json = [];
        foreach ($this as $key => $value) {
            $key = Functions::unCamelize($key);
            $json[$key] = $value;
        }

        return $json;
    }
}
