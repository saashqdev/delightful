<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Admin\Agent\Assembler;

use App\Application\Admin\Agent\DTO\AdminAgentDetailDTO;
use App\Application\Admin\Agent\DTO\AdminAgentDTO;
use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;

class AgentAssembler
{
    // entity to dto
    public static function entityToDTO(DelightfulAgentEntity $entity): AdminAgentDTO
    {
        return new AdminAgentDTO($entity->toArray());
    }

    public static function toAdminAgentDetail(DelightfulAgentEntity $agentEntity, DelightfulAgentVersionEntity $agentVersionEntity): AdminAgentDetailDTO
    {
        $adminAgentDetailDTO = new AdminAgentDetailDTO();
        $adminAgentDetailDTO->setId($agentEntity->getId());
        $adminAgentDetailDTO->setAgentName($agentVersionEntity->getAgentName());
        $adminAgentDetailDTO->setAgentDescription($agentVersionEntity->getAgentDescription());
        $adminAgentDetailDTO->setCreatedUid($agentEntity->getCreatedUid());
        $adminAgentDetailDTO->setVersionNumber($agentVersionEntity->getVersionNumber());
        $adminAgentDetailDTO->setStatus($agentEntity->getStatus());
        $adminAgentDetailDTO->setVisibilityConfig($agentVersionEntity->getVisibilityConfig());
        $adminAgentDetailDTO->setAgentAvatar($agentVersionEntity->getAgentAvatar());
        $adminAgentDetailDTO->setCreatedAt($agentVersionEntity->getCreatedAt());
        return $adminAgentDetailDTO;
    }
}
