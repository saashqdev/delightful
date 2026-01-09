<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\ValueObject;

/**
 * load balancingstrategytype枚举.
 */
enum LoadBalancingType: int
{
    /**
     * 随机strategy - 随机chooseonecanuseback端service
     */
    case RANDOM = 1;

    /**
     * round询strategy - 按order依timechooseback端service
     */
    case ROUND_ROBIN = 2;

    /**
     * add权round询strategy - according toweightratio例chooseback端service
     */
    case WEIGHTED_ROUND_ROBIN = 3;

    /**
     * hashstrategy - according torequesthashvaluechooseback端service
     */
    case HASH = 4;

    /**
     * getload balancingtypedescriptiontext.
     *
     * @return string descriptiontext
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::RANDOM => '随机',
            self::ROUND_ROBIN => 'round询',
            self::WEIGHTED_ROUND_ROBIN => 'add权round询',
            self::HASH => 'hash',
        };
    }

    /**
     * checkcurrentload balancingtypewhethersupportweight.
     *
     * @return bool whethersupportweight
     */
    public function supportsWeight(): bool
    {
        return $this === self::WEIGHTED_ROUND_ROBIN;
    }

    /**
     * get所havecanuseload balancingtype.
     *
     * @return array<LoadBalancingType> load balancingtypearray
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::RANDOM,
            self::ROUND_ROBIN,
            self::WEIGHTED_ROUND_ROBIN,
        ];
    }
}
