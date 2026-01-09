<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ImageGenerate;

use App\Domain\ImageGenerate\Contract\ImageEnhancementProcessorInterface;
use App\Domain\ImageGenerate\ValueObject\ImplicitWatermark;

/**
 * nullimplementimageenhanceprocessdevice
 * innothavequotientindustrycodeo clockprovidedefaultimplement.
 */
class NullImageEnhancementProcessor implements ImageEnhancementProcessorInterface
{
    public function enhanceImageData(string $imageData, ImplicitWatermark $watermark): string
    {
        // opensourceversionnotconductanyenhanceprocess,directlyreturnoriginaldata
        return $imageData;
    }

    public function enhanceImageUrl(string $imageUrl, ImplicitWatermark $watermark): string
    {
        // opensourceversionnotconductanyenhanceprocess,directlyreturnoriginalURL
        return $imageUrl;
    }

    public function extractEnhancementFromImageData(string $imageData): ?array
    {
        // opensourceversionnomethodextractenhanceinformation
        return null;
    }
}
