<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Factory;

use App\Domain\Agent\Entity\DelightfulAgentEntity;
use App\Domain\Agent\Repository\Persistence\Model\DelightfulAgentModel;

class DelightfulAgentFactory
{
    public static function modelToEntity(DelightfulAgentModel $model): DelightfulAgentEntity
    {
        $entityArray = $model->toArray();
        return self::toEntity($entityArray);
    }

    public static function toEntity(array $bot): DelightfulAgentEntity
    {
        $magicAgentEntity = new DelightfulAgentEntity($bot);
        if (isset($bot['last_version_info'])) {
            $lastVersionInfo = $magicAgentEntity->getLastVersionInfo();
            $lastVersionInfo['agent_id'] = $lastVersionInfo['root_id'];
            $lastVersionInfo['agent_name'] = $lastVersionInfo['robot_name'];
            $lastVersionInfo['agent_description'] = $lastVersionInfo['robot_description'];
            $lastVersionInfo['agent_avatar'] = $lastVersionInfo['robot_avatar'];
            $magicAgentEntity->setLastVersionInfo($lastVersionInfo);
        }
        return $magicAgentEntity;
    }

    public static function toEntities(array $bots): array
    {
        if (empty($bots)) {
            return [];
        }
        $botEntities = [];
        foreach ($bots as $bot) {
            $botEntities[] = self::toEntity((array) $bot);
        }
        return $botEntities;
    }

    /**
     * @param $botEntities DelightfulAgentEntity[]
     */
    public static function toArrays(array $botEntities): array
    {
        if (empty($botEntities)) {
            return [];
        }
        $result = [];
        foreach ($botEntities as $entity) {
            $result[] = $entity->toArray();
        }
        return $result;
    }
}
