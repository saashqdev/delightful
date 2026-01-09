<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ImageGenerate;

use App\Domain\ImageGenerate\Contract\FontProviderInterface;

/**
 * defaultfieldbodyprovidepersonimplement
 * opensourceprojectmiddledefaultimplement,providefoundationfieldbodyfeature
 * enterpriseprojectcanpassdependencyinjectioncoveragethisimplementcomeprovidehighlevelfieldbodyfeature.
 */
class DefaultFontProvider implements FontProviderInterface
{
    /**
     * getTTFfieldbodyfilepath.
     * opensourceversionnotprovideTTFfieldbodyfile.
     */
    public function getFontPath(): ?string
    {
        return null;
    }

    /**
     * detectwhethersupportTTFfieldbodyrender.
     * opensourceversiononlysupportinsideset fieldbody.
     */
    public function supportsTTF(): bool
    {
        return false;
    }

    /**
     * detecttextwhethercontainmiddletextcharacter.
     * opensourceversionview havetextfornonmiddletext,useinsideset fieldbodyrender.
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
            // adjustcolorboardgraphlikechecktransparentcolorindex
            return imagecolortransparent($image) !== -1;
        }

        // truecolorcolorgraphlikecheckalphachannel
        $width = imagesx($image);
        $height = imagesy($image);

        // samplingcheck,avoidcheckeachlikeelementimproveperformance
        $sampleSize = min(50, $width, $height);
        $stepX = max(1, (int) ($width / $sampleSize));
        $stepY = max(1, (int) ($height / $sampleSize));

        for ($x = 0; $x < $width; $x += $stepX) {
            for ($y = 0; $y < $height; $y += $stepY) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                if ($alpha > 0) {
                    return true; // hairshowtransparentlikeelement
                }
            }
        }

        return false;
    }
}
