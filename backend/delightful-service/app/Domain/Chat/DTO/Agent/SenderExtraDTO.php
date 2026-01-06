<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Agent;

use App\Infrastructure\Core\AbstractDTO;

class SenderExtraDTO extends AbstractDTO
{
    protected ?int $delightfulEnvId = null;

    public function getDelightfulEnvId(): ?int
    {
        return $this->delightfulEnvId;
    }

    public function setDelightfulEnvId(?int $delightfulEnvId): self
    {
        $this->delightfulEnvId = $delightfulEnvId;
        return $this;
    }
}
