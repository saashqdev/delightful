<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Admin\Entity\ValueObject\Extra;

class DefaultFriendExtra extends AbstractSettingExtra
{
    protected array $selectedAgentIds = [];

    public function getSelectedAgentIds(): array
    {
        return $this->selectedAgentIds;
    }

    public function setSelectedAgentIds(array $selectedAgentIds): self
    {
        $this->selectedAgentIds = $selectedAgentIds;
        return $this;
    }
}
