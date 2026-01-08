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
        $list = array_map(fn (DelightfulAgentEntity $delightfulAgentEntity) => $this->createAgentDTO($delightfulAgentEntity, $avatars), $list);
        return new PageDTO($page->getPage(), $total, $list);
    }

    public static function createAgentV1Response(DelightfulAgentVO $delightfulAgentVO, DelightfulFlowDTO $delightfulFlowDTO): array
    {
        $agentEntity = $delightfulAgentVO->getAgentEntity();
        $agentArray = $agentEntity->toArray();
        $agentArray['bot_version_id'] = $agentEntity->getAgentVersionId();
        $agentArray['robot_avatar'] = $agentEntity->getAgentAvatar();
        $agentArray['robot_name'] = $agentEntity->getAgentName();
        $agentArray['robot_description'] = $agentEntity->getAgentDescription();

        $result['agent_version_entity'] = [];

        $delightfulAgentVersionEntity = $delightfulAgentVO->getAgentVersionEntity();
        if ($delightfulAgentVersionEntity) {
            $agentVersionArray = $delightfulAgentVersionEntity->toArray();
            $agentVersionArray['robot_version_id'] = $delightfulAgentVersionEntity->getAgentName();
            $agentVersionArray['robot_avatar'] = $delightfulAgentVersionEntity->getAgentAvatar();
            $agentVersionArray['robt_name'] = $delightfulAgentVersionEntity->getAgentName();
            $agentVersionArray['robot_description'] = $delightfulAgentVersionEntity->getAgentDescription();
            $result['agent_version_entity'] = $agentVersionArray;
        }

        $result = [];
        $result['agent_entity'] = $agentArray;
        $result['delightful_user_entity'] = $delightfulAgentVO->getDelightfulUserEntity();
        $result['delightful_flow_entity'] = $delightfulFlowDTO;
        $result['agent_version_entity'] = $delightfulAgentVO->getAgentVersionEntity();
        $result['is_add'] = $delightfulAgentVO->getIsAdd();

        $result['botVersionEntity'] = $delightfulAgentVO->getAgentVersionEntity();
        $result['botEntity'] = $agentArray;
        $result['delightfulUserEntity'] = $delightfulAgentVO->getDelightfulUserEntity();
        $result['delightfulFlowEntity'] = $delightfulFlowDTO;
        $result['isAdd'] = $delightfulAgentVO->getIsAdd();
        return $result;
    }
}
