<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

/**
 * fieldbodyprovidepersoninterface
 * useatinopensourceprojectmiddledefinitionfieldbodymanagestandard,byenterpriseprojectimplementspecificlogic.
 */
interface FontProviderInterface
{
    /**
     * getTTFfieldbodyfilepath.
     *
     * @return null|string fieldbodyfile绝topath,iffornullthennot supportedTTFfieldbody
     */
    public function getFontPath(): ?string;

    /**
     * detectwhethersupportTTFfieldbodyrender.
     *
     * @return bool truetableshowsupportTTFfieldbody,falsetableshowonlysupportinsideset fieldbody
     */
    public function supportsTTF(): bool;

    /**
     * detecttextwhethercontainmiddletextcharacter.
     *
     * @param string $text wantdetecttext
     * @return bool truetableshowcontainmiddletextcharacter,falsetableshownotcontain
     */
    public function containsChinese(string $text): bool;

    /**
     * detectgraphlikewhethercontaintransparentchannel.
     *
     * @param mixed $image GDgraphlikeresource
     * @return bool truetableshowcontaintransparentdegree,falsetableshownotcontain
     */
    public function hasTransparency($image): bool;
}
