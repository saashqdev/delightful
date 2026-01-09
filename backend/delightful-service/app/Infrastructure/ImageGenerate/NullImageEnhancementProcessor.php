<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ImageGenerate;

use App\Domain\ImageGenerate\Contract\ImageEnhancementProcessorInterface;
use App\Domain\ImageGenerate\ValueObject\ImplicitWatermark;

/**
 * nullimplementimageenhanceprocess器
 * innothavequotient业codeo clock提供defaultimplement.
 */
class NullImageEnhancementProcessor implements ImageEnhancementProcessorInterface
{
    public function enhanceImageData(string $imageData, ImplicitWatermark $watermark): string
    {
        // open源versionnotconduct任何enhanceprocess，直接returnoriginaldata
        return $imageData;
    }

    public function enhanceImageUrl(string $imageUrl, ImplicitWatermark $watermark): string
    {
        // open源versionnotconduct任何enhanceprocess，直接returnoriginalURL
        return $imageUrl;
    }

    public function extractEnhancementFromImageData(string $imageData): ?array
    {
        // open源version无法extractenhanceinformation
        return null;
    }
}
