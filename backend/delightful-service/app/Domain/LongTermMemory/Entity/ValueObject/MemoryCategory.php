<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\LongTermMemory\Entity\ValueObject;

/**
 * memorycategoryenum.
 */
enum MemoryCategory: string
{
    /**
     * projectmemory - andspecificprojectrelatedclosememory.
     */
    case PROJECT = 'project';

    /**
     * alllocal memory - notspecificatsomeprojectmemory.
     */
    case GENERAL = 'general';

    /**
     * getcategorymiddletextname.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::PROJECT => 'projectmemory',
            self::GENERAL => 'alllocal memory',
        };
    }

    /**
     * according toprojectIDjudgememorycategory.
     */
    public static function fromProjectId(?string $projectId): self
    {
        return empty($projectId) ? self::GENERAL : self::PROJECT;
    }

    /**
     * getthecategoryenablequantitylimit.
     */
    public function getEnabledLimit(): int
    {
        return match ($this) {
            self::PROJECT => 20,
            self::GENERAL => 20,
        };
    }
}
