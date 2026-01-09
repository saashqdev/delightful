<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\Contract;

/**
 * 字体提供者interface
 * 用于在开源项目中定义字体管理standard，由企业项目implement具体逻辑.
 */
interface FontProviderInterface
{
    /**
     * getTTF字体filepath.
     *
     * @return null|string 字体file的绝对path，如果为null则不支持TTF字体
     */
    public function getFontPath(): ?string;

    /**
     * 检测是否支持TTF字体渲染.
     *
     * @return bool truetable示支持TTF字体，falsetable示仅支持内置字体
     */
    public function supportsTTF(): bool;

    /**
     * 检测文本是否contain中文字符.
     *
     * @param string $text 要检测的文本
     * @return bool truetable示contain中文字符，falsetable示不contain
     */
    public function containsChinese(string $text): bool;

    /**
     * 检测图像是否contain透明通道.
     *
     * @param mixed $image GD图像资源
     * @return bool truetable示contain透明度，falsetable示不contain
     */
    public function hasTransparency($image): bool;
}
