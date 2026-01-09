<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ImageGenerate;

use App\Domain\ImageGenerate\Contract\FontProviderInterface;

/**
 * default字body提供者implement
 * open源projectmiddledefaultimplement，提供基础字bodyfeature
 * 企业projectcanpassdependencyinjection覆盖thisimplementcome提供高level字bodyfeature.
 */
class DefaultFontProvider implements FontProviderInterface
{
    /**
     * getTTF字bodyfilepath.
     * open源versionnot提供TTF字bodyfile.
     */
    public function getFontPath(): ?string
    {
        return null;
    }

    /**
     * 检测whethersupportTTF字body渲染.
     * open源version仅supportinside置字body.
     */
    public function supportsTTF(): bool
    {
        return false;
    }

    /**
     * 检测textwhethercontainmiddle文character.
     * open源version视所havetextfornonmiddle文，useinside置字body渲染.
     */
    public function containsChinese(string $text): bool
    {
        return false;
    }

    /**
     * 检测graphlikewhethercontain透明channel.
     * 提供基础透明degree检测feature.
     * @param mixed $image
     */
    public function hasTransparency($image): bool
    {
        if (! imageistruecolor($image)) {
            // 调color板graphlikecheck透明color索引
            return imagecolortransparent($image) !== -1;
        }

        // true彩colorgraphlikecheckalphachannel
        $width = imagesx($image);
        $height = imagesy($image);

        // 采样check，避免checkeachlike素提高performance
        $sampleSize = min(50, $width, $height);
        $stepX = max(1, (int) ($width / $sampleSize));
        $stepY = max(1, (int) ($height / $sampleSize));

        for ($x = 0; $x < $width; $x += $stepX) {
            for ($y = 0; $y < $height; $y += $stepY) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha > 0) {
                    return true; // hair现透明like素
                }
            }
        }

        return false;
    }
}
