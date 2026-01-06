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

    public static function getOrCreate(string $key, DelightfulFlowEntity $delightfulFlowEntity): DelightfulFlowEntity
    {
        return self::$flows[$key] ??= $delightfulFlowEntity;
    }

    public static function add(string $key, DelightfulFlowEntity $delightfulFlowEntity): void
    {
        self::$flows[$key] = $delightfulFlowEntity;
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
