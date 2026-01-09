<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ImageGenerate;

use App\Domain\ImageGenerate\Contract\FontProviderInterface;

/**
 * default字体提供者implement
 * 开源project中的defaultimplement，提供基础字体feature
 * 企业projectcanpassdependency注入覆盖此implement来提供高级字体feature.
 */
class DefaultFontProvider implements FontProviderInterface
{
    /**
     * getTTF字体filepath.
     * 开源versionnot提供TTF字体file.
     */
    public function getFontPath(): ?string
    {
        return null;
    }

    /**
     * 检测whethersupportTTF字体渲染.
     * 开源version仅support内置字体.
     */
    public function supportsTTF(): bool
    {
        return false;
    }

    /**
     * 检测文本whethercontain中文字符.
     * 开源version视所have文本为non中文，use内置字体渲染.
     */
    public function containsChinese(string $text): bool
    {
        return false;
    }

    /**
     * 检测图像whethercontain透明通道.
     * 提供基础的透明度检测feature.
     * @param mixed $image
     */
    public function hasTransparency($image): bool
    {
        if (! imageistruecolor($image)) {
            // 调色板图像check透明色索引
            return imagecolortransparent($image) !== -1;
        }

        // 真彩色图像checkalpha通道
        $width = imagesx($image);
        $height = imagesy($image);

        // 采样check，避免checkeach个像素提高performance
        $sampleSize = min(50, $width, $height);
        $stepX = max(1, (int) ($width / $sampleSize));
        $stepY = max(1, (int) ($height / $sampleSize));

        for ($x = 0; $x < $width; $x += $stepX) {
            for ($y = 0; $y < $height; $y += $stepY) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha > 0) {
                    return true; // 发现透明像素
                }
            }
        }

        return false;
    }
}
