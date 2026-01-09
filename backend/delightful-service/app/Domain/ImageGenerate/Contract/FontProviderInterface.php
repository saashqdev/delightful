<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

/**
 * 字体提供者interface
 * useatin开源project中定义字体管理standard，由企业projectimplementspecific逻辑.
 */
interface FontProviderInterface
{
    /**
     * getTTF字体filepath.
     *
     * @return null|string 字体file的绝对path，if为nullthennot supportedTTF字体
     */
    public function getFontPath(): ?string;

    /**
     * 检测whethersupportTTF字体渲染.
     *
     * @return bool truetable示supportTTF字体，falsetable示仅support内置字体
     */
    public function supportsTTF(): bool;

    /**
     * 检测文本whethercontain中文字符.
     *
     * @param string $text 要检测的文本
     * @return bool truetable示contain中文字符，falsetable示notcontain
     */
    public function containsChinese(string $text): bool;

    /**
     * 检测图像whethercontain透明通道.
     *
     * @param mixed $image GD图像资源
     * @return bool truetable示contain透明度，falsetable示notcontain
     */
    public function hasTransparency($image): bool;
}
