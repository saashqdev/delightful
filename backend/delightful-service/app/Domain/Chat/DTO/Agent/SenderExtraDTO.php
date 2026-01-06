<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Agent;

use App\Infrastructure\Core\AbstractDTO;

class SenderExtraDTO extends AbstractDTO
{
    protected ?int $magicEnvId = null;

    public function getDelightfulEnvId(): ?int
    {
        return $this->magicEnvId;
    }

    public function setDelightfulEnvId(?int $magicEnvId): self
    {
        $this->magicEnvId = $magicEnvId;
        return $this;
    }
}
