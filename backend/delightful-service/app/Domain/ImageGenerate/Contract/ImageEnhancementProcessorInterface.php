<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

use App\Domain\ImageGenerate\ValueObject\ImplicitWatermark;

/**
 * imageenhanceprocess器interface
 * useatforimageembeddingenhanceinformation(如隱typewatermarketc).
 */
interface ImageEnhancementProcessorInterface
{
    /**
     * forimagedataembeddingenhanceinformation.
     */
    public function enhanceImageData(string $imageData, ImplicitWatermark $watermark): string;

    /**
     * forimageURLembeddingenhanceinformation.
     */
    public function enhanceImageUrl(string $imageUrl, ImplicitWatermark $watermark): string;

    /**
     * fromimagedataextractenhanceinformation.
     */
    public function extractEnhancementFromImageData(string $imageData): ?array;
}
