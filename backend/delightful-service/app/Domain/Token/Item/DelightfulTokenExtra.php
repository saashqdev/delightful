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
    protected ?int $delightfulEnvId = null;

    public function getDelightfulEnvId(): ?int
    {
        return $this->delightfulEnvId;
    }

    public function setDelightfulEnvId(?int $delightfulEnvId): void
    {
        $this->delightfulEnvId = $delightfulEnvId;
    }

    public function setTokenExtraData(array $extraData): self
    {
        if (isset($extraData['delightful_env_id'])) {
            $this->setDelightfulEnvId($extraData['delightful_env_id']);
        }
        return $this;
    }
}
