<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Token\Item;

use App\Domain\Token\Entity\AbstractEntity;
use App\Domain\Token\Repository\Facade\DelightfulTokenExtraInterface;

class DelightfulTokenExtra extends AbstractEntity implements DelightfulTokenExtraInterface
{
    protected ?int $magicEnvId = null;

    public function getDelightfulEnvId(): ?int
    {
        return $this->magicEnvId;
    }

    public function setDelightfulEnvId(?int $magicEnvId): void
    {
        $this->magicEnvId = $magicEnvId;
    }

    public function setTokenExtraData(array $extraData): self
    {
        if (isset($extraData['magic_env_id'])) {
            $this->setDelightfulEnvId($extraData['magic_env_id']);
        }
        return $this;
    }
}
