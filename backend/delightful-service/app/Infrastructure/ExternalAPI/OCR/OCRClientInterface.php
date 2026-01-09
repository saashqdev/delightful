<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\OCR;

interface OCRClientInterface
{
    /**
     *  OCR request,itemfrontonlysupport pdf and image.
     *
     * @param null|string $url graphlike URL groundaddress|graphlike Base64 encoding
     * @return string OCR processbackresult
     */
    public function ocr(?string $url = null): string;
}
