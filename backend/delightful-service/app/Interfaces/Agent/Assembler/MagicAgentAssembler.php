<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Agent\Assembler;

use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\VO\DelightfulAgentVO;
use App\Infrastructure\Core\ValueObject\Page;
use App\Interfaces\Agent\DTO\DelightfulAgentDTO;
use App\Interfaces\Flow\DTO\Flow\DelightfulFlowDTO;
use App\Interfaces\Kernel\DTO\PageDTO;

class DelightfulAgentAssembler
{
    public function createAgentDTO(DelightfulAgentEntity $agentEntity, array $avatars = []): DelightfulAgentDTO
    {
        $agentArray = $agentEntity->toArray();
        $DTO = new DelightfulAgentDTO($agentArray);

        $DTO->setAgentAvatar(FileAssembler::getUrl($avatars[$agentEntity->getAgentAvatar()] ?? null));
        $DTO->setAgentVersion($agentEntity->getLastVersionInfo());
        $DTO->setAgentName($agentEntity->getAgentName());
        $DTO->setAgentDescription($agentEntity->getAgentDescription());
        $DTO->setAgentVersionId($agentEntity->getAgentVersionId());
        return $DTO;
    }

    public function createPageListAgentDTO(int $total, array $list, Page $page, array $avatars = []): PageDTO
    {
        $list = array_map(fn (DelightfulAgentEntity $magicAgentEntity) => $this->createAgentDTO($magicAgentEntity, $avatars), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }

    public static function createAgentV1Response(DelightfulAgentVO $magicAgentVO, DelightfulFlowDTO $magicFlowDTO): array
    {
        $agentEntity = $magicAgentVO->getAgentEntity();
        $agentArray = $agentEntity->toArray();
        $agentArray['bot_version_id'] = $agentEntity->getAgentVersionId();
        $agentArray['robot_avatar'] = $agentEntity->getAgentAvatar();
        $agentArray['robot_name'] = $agentEntity->getAgentName();
        $agentArray['robot_description'] = $agentEntity->getAgentDescription();

        $result['agent_version_entity'] = [];

        $magicAgentVersionEntity = $magicAgentVO->getAgentVersionEntity();
        if ($magicAgentVersionEntity) {
            $agentVersionArray = $magicAgentVersionEntity->toArray();
            $agentVersionArray['robot_version_id'] = $magicAgentVersionEntity->getAgentName();
            $agentVersionArray['robot_avatar'] = $magicAgentVersionEntity->getAgentAvatar();
            $agentVersionArray['robt_name'] = $magicAgentVersionEntity->getAgentName();
            $agentVersionArray['robot_description'] = $magicAgentVersionEntity->getAgentDescription();
            $result['agent_version_entity'] = $agentVersionArray;
        }

        $result = [];
        $result['agent_entity'] = $agentArray;
        $result['magic_user_entity'] = $magicAgentVO->getDelightfulUserEntity();
        $result['magic_flow_entity'] = $magicFlowDTO;
        $result['agent_version_entity'] = $magicAgentVO->getAgentVersionEntity();
        $result['is_add'] = $magicAgentVO->getIsAdd();

        $result['botVersionEntity'] = $magicAgentVO->getAgentVersionEntity();
        $result['botEntity'] = $agentArray;
        $result['magicUserEntity'] = $magicAgentVO->getDelightfulUserEntity();
        $result['magicFlowEntity'] = $magicFlowDTO;
        $result['isAdd'] = $magicAgentVO->getIsAdd();
        return $result;
    }
}
