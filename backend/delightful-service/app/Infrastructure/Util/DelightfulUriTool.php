<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util;

class DelightfulUriTool
{
    public static function getImagesGenerationsUri(): string
    {
        return '/v2/images/generations';
    }

    public static function getModelsUri(): string
    {
        return '/v1/models';
    }
}
