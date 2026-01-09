<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Mode\Entity;

enum DistributionTypeEnum: int
{
    /**
     * independentconfigurationmode.
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
            self::INDEPENDENT => 'independentconfiguration',
            self::INHERITED => 'inheritconfiguration',
        };
    }

    /**
     * getEnglishidentifier.
     */
    public function getIdentifier(): string
    {
        return match ($this) {
            self::INDEPENDENT => 'independent',
            self::INHERITED => 'inherited',
        };
    }

    /**
     * whetherforindependentconfiguration.
     */
    public function isIndependent(): bool
    {
        return $this === self::INDEPENDENT;
    }

    /**
     * whetherforinheritconfiguration.
     */
    public function isInherited(): bool
    {
        return $this === self::INHERITED;
    }

    /**
     * get havetype.
     */
    public static function getAllTypes(): array
    {
        return [
            self::INDEPENDENT,
            self::INHERITED,
        ];
    }

    /**
     * fromvaluecreateenum.
     */
    public static function fromValue(int $value): self
    {
        return self::from($value);
    }

    /**
     * getoptionarray(useatfrontclientshow).
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
