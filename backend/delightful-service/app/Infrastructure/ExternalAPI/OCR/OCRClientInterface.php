<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\OCR;

interface OCRClientInterface
{
    /**
     *  OCR request,目front只support pdf 和 image.
     *
     * @param null|string $url graph像的 URL ground址|graph像的 Base64 encoding
     * @return string OCR processback的result
     */
    public function ocr(?string $url = null): string;
}
