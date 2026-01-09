<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

use App\Domain\ImageGenerate\ValueObject\ImplicitWatermark;

/**
 * image增强process器interface
 * useat为image嵌入增强information（如隐type水印etc）.
 */
interface ImageEnhancementProcessorInterface
{
    /**
     * 为imagedata嵌入增强information.
     */
    public function enhanceImageData(string $imageData, ImplicitWatermark $watermark): string;

    /**
     * 为imageURL嵌入增强information.
     */
    public function enhanceImageUrl(string $imageUrl, ImplicitWatermark $watermark): string;

    /**
     * fromimagedata提取增强information.
     */
    public function extractEnhancementFromImageData(string $imageData): ?array;
}
