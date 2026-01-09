<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\OCR;

interface OCRClientInterface
{
    /**
     *  OCR request,目前只支持 pdf 和 image.
     *
     * @param null|string $url 图像的 URL 地址|图像的 Base64 编码
     * @return string OCR process后的结果
     */
    public function ocr(?string $url = null): string;
}
