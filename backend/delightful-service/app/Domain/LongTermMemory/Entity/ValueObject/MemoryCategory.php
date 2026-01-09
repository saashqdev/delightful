<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\LongTermMemory\Entity\ValueObject;

/**
 * 记忆category枚举.
 */
enum MemoryCategory: string
{
    /**
     * project记忆 - 与特定project相关的记忆.
     */
    case PROJECT = 'project';

    /**
     * all局记忆 - not特定atsomeproject的记忆.
     */
    case GENERAL = 'general';

    /**
     * getcategory的middle文name.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::PROJECT => 'project记忆',
            self::GENERAL => 'all局记忆',
        };
    }

    /**
     * according toprojectID判断记忆category.
     */
    public static function fromProjectId(?string $projectId): self
    {
        return empty($projectId) ? self::GENERAL : self::PROJECT;
    }

    /**
     * get该category的enablequantity限制.
     */
    public function getEnabledLimit(): int
    {
        return match ($this) {
            self::PROJECT => 20,
            self::GENERAL => 20,
        };
    }
}
