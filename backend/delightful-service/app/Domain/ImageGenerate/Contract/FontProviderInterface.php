<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

/**
 * 字bodyprovidepersoninterface
 * useatinopensourceprojectmiddledefinition字bodymanagestandard,byenterpriseprojectimplementspecificlogic.
 */
interface FontProviderInterface
{
    /**
     * getTTF字bodyfilepath.
     *
     * @return null|string 字bodyfile绝topath,iffornullthennot supportedTTF字body
     */
    public function getFontPath(): ?string;

    /**
     * detectwhethersupportTTF字bodyrender.
     *
     * @return bool truetableshowsupportTTF字body,falsetableshowonlysupportinsideset fieldbody
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
