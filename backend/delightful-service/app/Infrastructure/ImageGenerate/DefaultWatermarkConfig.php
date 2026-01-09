<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ImageGenerate;

use App\Domain\ImageGenerate\Contract\WatermarkConfigInterface;
use App\Domain\ImageGenerate\ValueObject\WatermarkConfig;

/**
 * defaultwatermarkconfigurationimplement
 * open源projectmiddledefaultimplement，notenablewatermark
 * 企业projectcanpassinheritor重新implementcome提供specificwatermark逻辑.
 */
class DefaultWatermarkConfig implements WatermarkConfigInterface
{
    public function getWatermarkConfig(?string $orgCode = null): ?WatermarkConfig
    {
        // open源versiondefaultnotenablewatermark
        return new WatermarkConfig(
            logoTextContent: 'Delightful AI Generated', // defaultwatermarktext
            position: 3, // rightdownangle
            opacity: 0.3, // 30% 透明degree,
        );
    }
}
