<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Admin\DTO\Extra;

class DefaultFriendExtraDTO extends AbstractSettingExtraDTO
{
    public array $selectedAgentIds = [];

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
