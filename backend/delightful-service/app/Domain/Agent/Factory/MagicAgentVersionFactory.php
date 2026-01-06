<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Agent\Factory;

use App\Domain\Agent\Entity\DelightfulAgentVersionEntity;
use Hyperf\Codec\Json;

class DelightfulAgentVersionFactory
{
    public static function toEntity(array $botVersion): DelightfulAgentVersionEntity
    {
        if (isset($botVersion['instructs']) && is_string($botVersion['instructs'])) {
            $botVersion['instructs'] = Json::decode($botVersion['instructs']);
        }
        if (isset($botVersion['visibility_config']) && is_string($botVersion['visibility_config'])) {
            $botVersion['visibility_config'] = Json::decode($botVersion['visibility_config']);
        }
        return new DelightfulAgentVersionEntity($botVersion);
    }

    public static function toEntities(array $botVersions): array
    {
        if (empty($botVersions)) {
            return [];
        }
        $botEntities = [];
        foreach ($botVersions as $botVersion) {
            $botEntities[] = self::toEntity((array) $botVersion);
        }
        return $botEntities;
    }

    /**
     * @param $botVersionEntities DelightfulAgentVersionEntity[]
     */
    public static function toArrays(array $botVersionEntities): array
    {
        if (empty($botVersionEntities)) {
            return [];
        }
        $result = [];
        foreach ($botVersionEntities as $botVersionEntity) {
            $result[] = $botVersionEntity->toArray();
        }
        return $result;
    }
}
