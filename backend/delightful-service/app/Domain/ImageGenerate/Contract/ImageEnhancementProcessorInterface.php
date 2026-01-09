<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

use App\Domain\ImageGenerate\ValueObject\ImplicitWatermark;

/**
 * imageenhanceprocess器interface
 * useatforimage嵌入enhanceinformation(如隐typewatermarketc).
 */
interface ImageEnhancementProcessorInterface
{
    /**
     * forimagedata嵌入enhanceinformation.
     */
    public function enhanceImageData(string $imageData, ImplicitWatermark $watermark): string;

    /**
     * forimageURL嵌入enhanceinformation.
     */
    public function enhanceImageUrl(string $imageUrl, ImplicitWatermark $watermark): string;

    /**
     * fromimagedataextractenhanceinformation.
     */
    public function extractEnhancementFromImageData(string $imageData): ?array;
}
