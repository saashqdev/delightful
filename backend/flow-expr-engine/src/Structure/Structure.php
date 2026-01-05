<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure;

use Delightful\FlowExprEngine\Kernel\Traits\UnderlineObjectJsonSerializable;
use Delightful\FlowExprEngine\Structure\Expression\ExpressionItem;
use JsonSerializable;

abstract class Structure implements JsonSerializable
{
    use UnderlineObjectJsonSerializable;

    public StructureType $structureType;

    private string $componentId = '';

    public function toArray(): array
    {
        return json_decode($this->toString(), true);
    }

    public function toString(): string
    {
        return json_encode($this->jsonSerialize());
    }

    public function getComponentId(bool $auto = true): string
    {
        if ($auto && ! $this->componentId) {
            $this->componentId = uniqid('component-');
        }
        return $this->componentId;
    }

    public function setComponentId(string $componentId): void
    {
        $this->componentId = $componentId;
    }

    /**
     * @return ExpressionItem[]
     */
    public function getAllFieldsExpressionItem(): array
    {
        return [];
    }
}
