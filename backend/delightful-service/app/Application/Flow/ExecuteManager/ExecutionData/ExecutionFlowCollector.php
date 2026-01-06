<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\ExecutionData;

use App\Domain\Flow\Entity\DelightfulFlowEntity;

class ExecutionFlowCollector
{
    protected static array $flows = [];

    public static function getOrCreate(string $key, DelightfulFlowEntity $magicFlowEntity): DelightfulFlowEntity
    {
        return self::$flows[$key] ??= $magicFlowEntity;
    }

    public static function add(string $key, DelightfulFlowEntity $magicFlowEntity): void
    {
        self::$flows[$key] = $magicFlowEntity;
    }

    public static function get(string $key): ?DelightfulFlowEntity
    {
        return self::$flows[$key] ?? null;
    }

    public static function remove(string $key): void
    {
        unset(self::$flows[$key]);
    }

    public static function count(): int
    {
        return count(self::$flows);
    }
}
