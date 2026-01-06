<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ModelGateway\Entity\ValueObject;

class ModelGatewayOfficialApp
{
    public const string APP_CODE = 'Delightful';

    public static function isOfficialApp(string $code): bool
    {
        return strtolower($code) === strtolower(self::APP_CODE);
    }
}
