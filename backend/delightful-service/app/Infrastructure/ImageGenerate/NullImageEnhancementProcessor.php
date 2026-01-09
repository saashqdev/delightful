<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ImageGenerate;

use App\Domain\ImageGenerate\Contract\ImageEnhancementProcessorInterface;
use App\Domain\ImageGenerate\ValueObject\ImplicitWatermark;

/**
 * nullimplement的image增强处理器
 * 在没有商业代码时提供defaultimplement.
 */
class NullImageEnhancementProcessor implements ImageEnhancementProcessorInterface
{
    public function enhanceImageData(string $imageData, ImplicitWatermark $watermark): string
    {
        // 开源version不进行任何增强处理，直接returnoriginaldata
        return $imageData;
    }

    public function enhanceImageUrl(string $imageUrl, ImplicitWatermark $watermark): string
    {
        // 开源version不进行任何增强处理，直接returnoriginalURL
        return $imageUrl;
    }

    public function extractEnhancementFromImageData(string $imageData): ?array
    {
        // 开源version无法提取增强information
        return null;
    }
}
