<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\Query;

class DelightfulFlowApiKeyQuery extends Query
{
    public ?string $flowCode = null;

    public ?int $type = null;

    public ?string $creator = null;

    public function getFlowCode(): ?string
    {
        return $this->flowCode;
    }

    public function setFlowCode(?string $flowCode): void
    {
        $this->flowCode = $flowCode;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function setCreator(?string $creator): void
    {
        $this->creator = $creator;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(?int $type): void
    {
        $this->type = $type;
    }
}
