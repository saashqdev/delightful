<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\HighAvailability\ValueObject;

/**
 * 负载均衡strategytype枚举.
 */
enum LoadBalancingType: int
{
    /**
     * 随机strategy - 随机选择一可use的back端service
     */
    case RANDOM = 1;

    /**
     * round询strategy - 按顺序依time选择back端service
     */
    case ROUND_ROBIN = 2;

    /**
     * 加权round询strategy - according to权重ratio例选择back端service
     */
    case WEIGHTED_ROUND_ROBIN = 3;

    /**
     * hashstrategy - according torequest的hashvalue选择back端service
     */
    case HASH = 4;

    /**
     * get负载均衡type的description文本.
     *
     * @return string description文本
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::RANDOM => '随机',
            self::ROUND_ROBIN => 'round询',
            self::WEIGHTED_ROUND_ROBIN => '加权round询',
            self::HASH => 'hash',
        };
    }

    /**
     * checkcurrent负载均衡typewhethersupport权重.
     *
     * @return bool whethersupport权重
     */
    public function supportsWeight(): bool
    {
        return $this === self::WEIGHTED_ROUND_ROBIN;
    }

    /**
     * get所have可use的负载均衡type.
     *
     * @return array<LoadBalancingType> 负载均衡typearray
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
