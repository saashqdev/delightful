<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\ValueObject;

/**
 * load balancingstrategytypeenum.
 */
enum LoadBalancingType: int
{
    /**
     * randomstrategy - randomchooseonecanusebackclientservice
     */
    case RANDOM = 1;

    /**
     * roundquerystrategy - 按order依timechoosebackclientservice
     */
    case ROUND_ROBIN = 2;

    /**
     * add权roundquerystrategy - according toweightratioexamplechoosebackclientservice
     */
    case WEIGHTED_ROUND_ROBIN = 3;

    /**
     * hashstrategy - according torequesthashvaluechoosebackclientservice
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
            self::RANDOM => 'random',
            self::ROUND_ROBIN => 'roundquery',
            self::WEIGHTED_ROUND_ROBIN => 'add权roundquery',
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
     * get havecanuseload balancingtype.
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
