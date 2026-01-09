<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ImageGenerate;

use App\Domain\ImageGenerate\Contract\FontProviderInterface;

/**
 * default字bodyprovidepersonimplement
 * opensourceprojectmiddledefaultimplement,providefoundation字bodyfeature
 * enterpriseprojectcanpassdependencyinjectioncoveragethisimplementcomeprovidehighlevel字bodyfeature.
 */
class DefaultFontProvider implements FontProviderInterface
{
    /**
     * getTTF字bodyfilepath.
     * opensourceversionnotprovideTTF字bodyfile.
     */
    public function getFontPath(): ?string
    {
        return null;
    }

    /**
     * detectwhethersupportTTF字bodyrender.
     * opensourceversiononlysupportinsideset fieldbody.
     */
    public function supportsTTF(): bool
    {
        return false;
    }

    /**
     * detecttextwhethercontainmiddletextcharacter.
     * opensourceversion視 havetextfornonmiddletext,useinsideset fieldbodyrender.
     */
    public function containsChinese(string $text): bool
    {
        return false;
    }

    /**
     * detectgraphlikewhethercontaintransparentchannel.
     * providefoundationtransparentdegreedetectfeature.
     * @param mixed $image
     */
    public function hasTransparency($image): bool
    {
        if (! imageistruecolor($image)) {
            // 調color板graphlikechecktransparentcolorindex
            return imagecolortransparent($image) !== -1;
        }

        // true彩colorgraphlikecheckalphachannel
        $width = imagesx($image);
        $height = imagesy($image);

        // samplingcheck,avoidcheckeachlike素improveperformance
        $sampleSize = min(50, $width, $height);
        $stepX = max(1, (int) ($width / $sampleSize));
        $stepY = max(1, (int) ($height / $sampleSize));

        for ($x = 0; $x < $width; $x += $stepX) {
            for ($y = 0; $y < $height; $y += $stepY) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha > 0) {
                    return true; // hairshowtransparentlike素
                }
            }
        }

        return false;
    }
}
