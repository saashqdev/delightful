<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI;

enum ImageGenerateType: string
{
    case URL = 'URL';
    case BASE_64 = 'base_64';

    public function isBase64(): bool
    {
        return $this === self::BASE_64;
    }
}
