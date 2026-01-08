<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\DTO\FlowVersion;

use App\Interfaces\Flow\Assembler\Flow\DelightfulFlowAssembler;
use App\Interfaces\Flow\DTO\AbstractFlowDTO;
use App\Interfaces\Flow\DTO\Flow\DelightfulFlowDTO;

class DelightfulFlowVersionDTO extends AbstractFlowDTO
{
    public string $name = '';

    public string $description = '';

    public string $flowCode;

    public ?DelightfulFlowDTO $delightfulFlow;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name ?? '';
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description ?? '';
    }

    public function getFlowCode(): string
    {
        return $this->flowCode;
    }

    public function setFlowCode(?string $flowCode): void
    {
        $this->flowCode = $flowCode ?? '';
    }

    public function getDelightfulFLow(): ?DelightfulFlowDTO
    {
        return $this->delightfulFlow;
    }

    public function setDelightfulFLow(mixed $delightfulFlow): void
    {
        $this->delightfulFlow = DelightfulFlowAssembler::createDelightfulFlowDTOByMixed($delightfulFlow);
    }
}
