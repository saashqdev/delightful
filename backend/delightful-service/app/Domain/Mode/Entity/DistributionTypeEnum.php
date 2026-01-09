<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Mode\Entity;

enum DistributionTypeEnum: int
{
    /**
     * 独立configurationmode.
     */
    case INDEPENDENT = 1;

    /**
     * inheritconfigurationmode.
     */
    case INHERITED = 2;

    /**
     * getdescription.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::INDEPENDENT => '独立configuration',
            self::INHERITED => 'inheritconfiguration',
        };
    }

    /**
     * get英文标识.
     */
    public function getIdentifier(): string
    {
        return match ($this) {
            self::INDEPENDENT => 'independent',
            self::INHERITED => 'inherited',
        };
    }

    /**
     * 是否为独立configuration.
     */
    public function isIndependent(): bool
    {
        return $this === self::INDEPENDENT;
    }

    /**
     * 是否为inheritconfiguration.
     */
    public function isInherited(): bool
    {
        return $this === self::INHERITED;
    }

    /**
     * get所有type.
     */
    public static function getAllTypes(): array
    {
        return [
            self::INDEPENDENT,
            self::INHERITED,
        ];
    }

    /**
     * 从valuecreate枚举.
     */
    public static function fromValue(int $value): self
    {
        return self::from($value);
    }

    /**
     * getoptionarray（用于前端展示）.
     */
    public static function getOptions(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->getDescription(),
            'identifier' => $type->getIdentifier(),
        ], self::getAllTypes());
    }
}
