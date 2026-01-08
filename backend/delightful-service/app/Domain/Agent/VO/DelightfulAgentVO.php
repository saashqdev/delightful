<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\VO;

use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use App\Domain\Contact\Entity\DelightfulUserEntity;
use App\Domain\Flow\Entity\DelightfulFlowEntity;

class DelightfulAgentVO
{
    public DelightfulAgentEntity $agentEntity;

    public DelightfulAgentEntity $botEntity;

    public ?DelightfulAgentVersionEntity $agentVersionEntity = null;

    public ?DelightfulAgentVersionEntity $botVersionEntity = null;

    public DelightfulUserEntity $delightfulUserEntity;

    public ?DelightfulFlowEntity $delightfulFlowEntity = null;

    public bool $isAdd = false;

    public function getAgentEntity(): DelightfulAgentEntity
    {
        return $this->agentEntity;
    }

    public function setAgentEntity(DelightfulAgentEntity $agentEntity): void
    {
        $this->agentEntity = $agentEntity;
        $this->botEntity = $agentEntity;
    }

    public function getAgentVersionEntity(): ?DelightfulAgentVersionEntity
    {
        return $this->agentVersionEntity;
    }

    public function setAgentVersionEntity(?DelightfulAgentVersionEntity $agentVersionEntity): void
    {
        $this->agentVersionEntity = $agentVersionEntity;
        $this->botVersionEntity = $agentVersionEntity;
    }

    public function getDelightfulUserEntity(): DelightfulUserEntity
    {
        return $this->delightfulUserEntity;
    }

    public function setDelightfulUserEntity(DelightfulUserEntity $delightfulUserEntity): void
    {
        $this->delightfulUserEntity = $delightfulUserEntity;
    }

    public function getDelightfulFlowEntity(): ?DelightfulFlowEntity
    {
        return $this->delightfulFlowEntity;
    }

    public function setDelightfulFlowEntity(?DelightfulFlowEntity $delightfulFlowEntity): void
    {
        $this->delightfulFlowEntity = $delightfulFlowEntity;
    }

    public function getIsAdd(): bool
    {
        return $this->isAdd;
    }

    public function setIsAdd(bool $isAdd): void
    {
        $this->isAdd = $isAdd;
    }
}
