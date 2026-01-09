<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ImageGenerate;

use App\Domain\ImageGenerate\Contract\WatermarkConfigInterface;
use App\Domain\ImageGenerate\ValueObject\WatermarkConfig;

/**
 * default水印configurationimplement
 * 开源项目中的defaultimplement，不启用水印
 * 企业项目canpass继承或重新implement来提供具体的水印逻辑.
 */
class DefaultWatermarkConfig implements WatermarkConfigInterface
{
    public function getWatermarkConfig(?string $orgCode = null): ?WatermarkConfig
    {
        // 开源versiondefault不启用水印
        return new WatermarkConfig(
            logoTextContent: 'Delightful AI Generated', // default水印文字
            position: 3, // 右下角
            opacity: 0.3, // 30% 透明度,
        );
    }
}
