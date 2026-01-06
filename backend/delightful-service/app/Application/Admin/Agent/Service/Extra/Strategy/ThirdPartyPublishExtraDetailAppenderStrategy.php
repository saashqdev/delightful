<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Admin\Agent\Service\Extra\Strategy;

use App\Application\Chat\Service\DelightfulAgentAppService;
use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Interfaces\Admin\DTO\Extra\SettingExtraDTOInterface;
use App\Interfaces\Admin\DTO\Extra\ThirdPartyPublishExtraDTO;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use InvalidArgumentException;

class ThirdPartyPublishExtraDetailAppenderStrategy implements ExtraDetailAppenderStrategyInterface
{
    public function appendExtraDetail(SettingExtraDTOInterface $extraDTO, DelightfulUserAuthorization $userAuthorization): SettingExtraDTOInterface
    {
        if (! $extraDTO instanceof ThirdPartyPublishExtraDTO) {
            throw new InvalidArgumentException('Expected ThirdPartyPublishExtraDTO');
        }

        $this->appendSelectedAgentsInfo($extraDTO, $userAuthorization);

        return $extraDTO;
    }

    public function appendSelectedAgentsInfo(ThirdPartyPublishExtraDTO $extraDTO, ?DelightfulUserAuthorization $userAuthorization): self
    {
        $agentRootIds = array_column($extraDTO->getSelectedAgents(), 'agent_id');
        $agentEntities = $this->getDelightfulAgentAppService()->getAgentsForAdmin($agentRootIds, $userAuthorization);
        /** @var array<int, DelightfulAgentEntity> $agentEntities */
        $agentEntities = array_column($agentEntities, null, 'id');
        foreach ($extraDTO->getSelectedAgents() as $selectedAgent) {
            $agentEntity = $agentEntities[(int) $selectedAgent->getAgentId()] ?? null;
            $selectedAgent->setName($agentEntity?->getAgentName())
                ->setAvatar($agentEntity?->getAgentAvatar());
        }
        return $this;
    }

    private function getDelightfulAgentAppService(): DelightfulAgentAppService
    {
        return di(DelightfulAgentAppService::class);
    }
}
