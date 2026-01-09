<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ImageGenerate;

use App\Domain\ImageGenerate\Contract\FontProviderInterface;

/**
 * default字bodyprovide者implement
 * open源projectmiddledefaultimplement,provide基础字bodyfeature
 * enterpriseprojectcanpassdependencyinjection覆盖thisimplementcomeprovidehighlevel字bodyfeature.
 */
class DefaultFontProvider implements FontProviderInterface
{
    /**
     * getTTF字bodyfilepath.
     * open源versionnotprovideTTF字bodyfile.
     */
    public function getFontPath(): ?string
    {
        return null;
    }

    /**
     * detectwhethersupportTTF字bodyrender.
     * open源versiononlysupportinsideset fieldbody.
     */
    public function supportsTTF(): bool
    {
        return false;
    }

    /**
     * detecttextwhethercontainmiddle文character.
     * open源version视所havetextfornonmiddle文,useinsideset fieldbodyrender.
     */
    public function containsChinese(string $text): bool
    {
        return false;
    }

    /**
     * detectgraphlikewhethercontaintransparentchannel.
     * provide基础transparentdegreedetectfeature.
     * @param mixed $image
     */
    public function hasTransparency($image): bool
    {
        if (! imageistruecolor($image)) {
            // 调color板graphlikechecktransparentcolorindex
            return imagecolortransparent($image) !== -1;
        }

        // true彩colorgraphlikecheckalphachannel
        $width = imagesx($image);
        $height = imagesy($image);

        // 采样check,avoidcheckeachlike素improveperformance
        $sampleSize = min(50, $width, $height);
        $stepX = max(1, (int) ($width / $sampleSize));
        $stepY = max(1, (int) ($height / $sampleSize));

        for ($x = 0; $x < $width; $x += $stepX) {
            for ($y = 0; $y < $height; $y += $stepY) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha > 0) {
                    return true; // hair现transparentlike素
                }
            }
        }

        return false;
    }
}
