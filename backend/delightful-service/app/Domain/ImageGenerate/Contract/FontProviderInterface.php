<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

/**
 * 字body提供者interface
 * useatin开源projectmiddledefinition字body管理standard，由企业projectimplementspecific逻辑.
 */
interface FontProviderInterface
{
    /**
     * getTTF字bodyfilepath.
     *
     * @return null|string 字bodyfile的绝对path，if为nullthennot supportedTTF字body
     */
    public function getFontPath(): ?string;

    /**
     * 检测whethersupportTTF字body渲染.
     *
     * @return bool truetable示supportTTF字body，falsetable示仅supportinside置字body
     */
    public function supportsTTF(): bool;

    /**
     * 检测textwhethercontainmiddle文character.
     *
     * @param string $text 要检测的text
     * @return bool truetable示containmiddle文character，falsetable示notcontain
     */
    public function containsChinese(string $text): bool;

    /**
     * 检测graph像whethercontain透明通道.
     *
     * @param mixed $image GDgraph像resource
     * @return bool truetable示contain透明degree，falsetable示notcontain
     */
    public function hasTransparency($image): bool;
}
