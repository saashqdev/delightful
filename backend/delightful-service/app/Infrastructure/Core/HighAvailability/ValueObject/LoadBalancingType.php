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
     * 随机strategy - 随机选择一个可用的后端service
     */
    case RANDOM = 1;

    /**
     * 轮询strategy - 按顺序依次选择后端service
     */
    case ROUND_ROBIN = 2;

    /**
     * 加权轮询strategy - according to权重比例选择后端service
     */
    case WEIGHTED_ROUND_ROBIN = 3;

    /**
     * hashstrategy - according torequest的hashvalue选择后端service
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
            self::ROUND_ROBIN => '轮询',
            self::WEIGHTED_ROUND_ROBIN => '加权轮询',
            self::HASH => 'hash',
        };
    }

    /**
     * checkcurrent负载均衡type是否support权重.
     *
     * @return bool 是否support权重
     */
    public function supportsWeight(): bool
    {
        return $this === self::WEIGHTED_ROUND_ROBIN;
    }

    /**
     * get所有可用的负载均衡type.
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
